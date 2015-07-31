<?php
class getImiAddresses
{
    // 出力ひな形
    public $output = array(
        'ic:ID' => '',
        'ic:種別' => '',
        'ic:表記' => '',
        'ic:郵便番号' => '',
        'ic:住所コード' => array(
            'ic:コード種別' => array(
                'ic:名称' => array(
                    'ic:種別' => '',
                    'ic:表記' => '全国地方公共団体コード',
                    'ic:カナ表記' => 'ゼンコクチホウコウキョウダンタイコード',
                    'ic:ローマ字表記' => ''
                ),
                'ic:発行者' => null,
                'ic:バージョン' => '',
                'ic:URI' => ''
            ),
            'ic:識別値' => ''
        ),
        'ic:国' => '',
        'ic:国コード' => array(
            'ic:コード種別' => array(
                'ic:名称' => array(
                    'ic:種別' => '',
                    'ic:表記' => 'ISO 3166-1',
                    'ic:カナ表記' => '',
                    'ic:ローマ字表記' => ''
                ),
                'ic:発行者' => null,
                'ic:バージョン' => '',
                'ic:URI' => ''
            ),
            'ic:識別値' => '392'
        ),
        'ic:都道府県' => '',
        'ic:都道府県コード' => array(
            'ic:コード種別' => array(
                'ic:名称' => array(
                    'ic:種別' => '',
                    'ic:表記' => '全国地方公共団体コード',
                    'ic:カナ表記' => 'ゼンコクチホウコウキョウダンタイコード',
                    'ic:ローマ字表記' => ''
                ),
                'ic:発行者' => null,
                'ic:バージョン' => '',
                'ic:URI' => ''
            ),
            'ic:識別値' => ''
        ),
        'ic:市区町村' => '',
        'ic:区' => '',
        'ic:市区町村コード' => array(
            'ic:コード種別' => array(
                'ic:名称' => array(
                    'ic:種別' => '',
                    'ic:表記' => '全国地方公共団体コード',
                    'ic:カナ表記' => 'ゼンコクチホウコウキョウダンタイコード',
                    'ic:ローマ字表記' => ''
                ),
                'ic:発行者' => null,
                'ic:バージョン' => '',
                'ic:URI' => ''
            ),
            'ic:識別値' => ''
        ),
        'ic:町名' => '',
        'ic:丁目' => '',
        'ic:番地補足' => '',
        'ic:番地' => '',
        'ic:号' => '',
        'ic:ビル名' => '',
        'ic:ビル番号' => '',
        'ic:部屋番号' => '',
        'ic:方書' => ''
    );

    // 都道府県<->住所データ対照
    // 町域以下の検索のため、「<配列キー名>.json」ファイルを参照する
    // 下記フォーマットでJSON化
    //  {"◯◯県××市": [
    //          { "zip": "000-0000",
    //            "code": "町域コード(都道府県コード+市町村コード+4桁)",
    //            "choiki": "町域名",
    //          }, ...
    //      ]
    //  }, ...
    private $prefs = array(
        'tohoku' => array(
            '北海道','青森','岩手','宮城','秋田','山形','福島'
        ),
        'kanto' => array(
            '茨城','栃木','群馬','埼玉','千葉','東京','神奈川','新潟','山梨','長野'
        ),
        'chubu' => array(
            '岐阜','静岡','愛知','三重','富山','石川','福井'
        ),
        'kinki' => array(
            '滋賀','京都','大阪','兵庫','奈良','和歌山'
        ),
        'chugoku' => array(
            '鳥取','島根','岡山','広島','山口','徳島','香川','愛媛','高知'
        ),
        'kyushu' => array(
            '福岡','佐賀','長崎','熊本','大分','宮崎','鹿児島','沖縄'
        )
    );

    // 政令市は「◯◯市◯◯区」と切り出されるので辞書で対応
    private $dcities = array(
        '札幌市','仙台市',
        'さいたま市','千葉市','横浜市','川崎市','相模原市',
        '新潟市','静岡市','浜松市','名古屋市',
        '京都市','大阪市','堺市','神戸市',
        '岡山市','広島市',
        '北九州市','福岡市','熊本市'
    );

    // 入力された住所格納用
    protected $addresses = array();
    // 住所データファイル格納用
    protected $data;
    // 住所データファイルパス
    private $datafile = "";
    private $datapath;

    // カンマ区切りのパラメータを配列として取得
    public function get_args_by_array($arg){
        $array = array();
        // パラメータ記法の揺れを考慮…
        if (is_array($arg)) {
            // [] を使った指定
            foreach ($arg as $v) {
                if (is_numeric($v)) {
                    $array[] = $v;
                }
            }
        } elseif (strpos($arg, ',') !== false) {
            // カンマ区切り
            $array = explode(',', $arg);
        }else{
            // 単一
            $array[] = $arg;
        }
        return $array;
    }

    // 町名以下, 丁目・番地・号分離
    public function split_lower_addresses($address_str){

        $_str = $address_str;

        // ◯丁目◯番地◯号
        if (preg_match('/([一二三四五六七八九十千百万0-9０-９-]+)(番$|番地|丁目|号$|号室$|階$|F$|Ｆ$)/u',$address_str,$_match)){
            if (!empty($_match)){

                // 数字
//            $num = trim($_match[1]);
                // 単位
                $sec = trim($_match[2]);
                // 丁目, 番地, 号, 号室であれば格納
                if ($sec == '号室' || $sec == '階' || $sec == 'F' || $sec == 'Ｆ'){
                    $sec = '部屋番号';
                }
                // 番は？
                if ($sec == '番'){
                    $sec = '番地';
                }

                if (array_key_exists('ic:'.$sec, $this->output)){
                    $this->output['ic:'.$sec] = $_match[0];
                };

                // さらに分割して繰り返し
                $ptn = $_match[0];
                $_tmp = explode($ptn,$address_str,2);
                $_str = $_tmp[0];

                if ($_tmp[0] == ""){
                    if (!empty($_tmp[1])){
                        $_str = $_tmp[1];
                    }
                };

                if ( count($_tmp) > 1 ) {
                    return $this->split_lower_addresses($_str);
                }
            }
        };
        return $_str;
    }

    // 数字から始まる住所の丁目、番地、号分け
    public function split_numeric_address($_addsubstr){

        // 半角に変換
        $_numstr = mb_convert_kana($_addsubstr,'an','utf8');
        $_numstr = preg_replace('/−|ー|―/u','-',$_numstr);

        // ハイフン付きは, 丁目・番地・号に分解しうる？
        if (preg_match("/-/u", $_numstr,$_match) == 1) {

            $delimiter = $_match[0];
            $_tmp = explode($delimiter, $_numstr, 3);
            $_max = max(array_keys($_tmp));

            // XXX-XX, XX-XX, X-XXXXX
            // 最大の「丁目」は北海道帯広市西19条南42丁目なので、番地から始まる住所と考える
            if ((intval($_tmp[0]) > 42) || $_max < 2) {
                if ($this->output['ic:番地'] == "") {
                    $this->output['ic:番地'] = $_tmp[0];
                }
                if ($this->output['ic:号'] == "" && !empty($_tmp[1])) {
                    $this->output['ic:号'] = $_tmp[1];
                }
            }

            // X-XX-XXX, X-XX-XXX-XXX
            if ($_max == 2) {
                if ($this->output['ic:丁目'] == "") {
                    $this->output['ic:丁目'] = $_tmp[0];
                }

                if ($this->output['ic:番地'] == "") {
                    $this->output['ic:番地'] = $_tmp[1];
                }

                if ($this->output['ic:号'] == "") {
                    $this->output['ic:号'] = $_tmp[2];
                }
            }

        } else {
            // 数字のみの場合, 番地と見なす
            if(is_numeric($_numstr)){
                if ($this->output['ic:番地'] == ""){
                    $this->output['ic:番地'] = $_numstr;
                }
            }
        }
        return;
    }

    // コンストラクタ
    //  引数はデータファイルのパス
    public function __construct($_datapath = "data/"){
        $this->datapath = $_datapath;
        $this->datafile = realpath($this->datapath .'kanazawaku.json');
        $_tmpdata = file_get_contents($this->datafile);
        $this->data = json_decode($_tmpdata, true);
    }

    public function doAnalyzeAddresses($address){

        $this->addresses = $this->get_args_by_array($address);

        if (!empty($this->addresses)) {
            foreach ($this->addresses as $_addstr) {
                // 余白削除
                $_addstr = preg_replace('/\s/', '', mb_convert_kana($_addstr, 's'));

                preg_match('/^(京都府|.+?[都道府県])(大和郡山市|蒲郡市|小郡市|郡上市|杵島郡大町町|佐波郡玉村町|(?:[^市]*?|余市|高市)郡.+?[町村]|(?:石狩|伊達|八戸|盛岡|奥州|南相馬|上越|姫路|宇陀|黒部|小諸|富山|岩国|周南|佐伯|西海)市|.*?[^0-9一二三四五六七八九十上下]区|四日市市|廿日市市|.+?市|.+?町|.+?村)(.*?)([0-9-０-９−]*?)$/u', $_addstr, $match);

                // 空配列削除/添字振り直し
                $match = array_values(array_filter($match, 'strlen'));

                // 町・大字・小字リスト検索用キー
                $_searchkey = "";

                foreach ($match as $_i => $_addsubstr) {

                    if ($_i > 0) {

                        // 番地, 丁目etc
                        // 数字から始まるパートは町名分離後であることが期待される
                        if (preg_match("/^[0-9０-９]+/u", $_addsubstr) == 1) {
                            $this->split_numeric_address($_addsubstr, $this->output);
                        }

                        // 市町村区以下
                        if (preg_match('/[区市町村]$/u', $_searchkey) == 1) {

                            if (array_key_exists($_searchkey, $this->data)) {
                                // 都道府県市町村コードまではこの段階で取得可能
                                $_tcode = $this->data[$_searchkey][0]['code'];
                                $this->output['ic:都道府県コード']['ic:識別値'] = substr($_tcode, 0, 2);
                                $this->output['ic:市区町村コード']['ic:識別値'] = substr($_tcode, 2, 5);

                                // 町域検索
                                $choiki_key = $_addsubstr;
                                $zip = "";
                                // 「茅ヶ崎中央」に対して「茅ヶ崎」が「茅ヶ崎中央」より先に引っかかる可能性がある為、
                                // ループは途中では抜けない。勿論、逆もありうるがデータの並び順が 単純->冗長 のようであるので、
                                $cnt = 0; // 検索ヒットカウンタ
                                foreach ($this->data[$_searchkey] as $area) {
                                    if (!empty($area['choiki'])){
                                        if (strpos($_addsubstr, $area['choiki']) === 0) {
                                            $choiki_key = $area['choiki'];
                                            $zip = $area['zip'];
                                            $cnt++;
                                        }
                                    }
                                }
                                if ($cnt > 0) {

                                    $this->output['ic:町名'] = $choiki_key;
                                    $this->output['ic:郵便番号'] = $zip;

                                    $ptn = $choiki_key;
                                    $_tmp = explode($ptn, $_addsubstr, 2);

                                    if (count($_tmp) > 1) {
                                        // 町名以下分割
                                        $_str = $this->split_lower_addresses($_tmp[1], $this->output);
                                        // 分割しきれない = ビル名、施設名？
                                        if (!empty($_str)) {
                                            if (preg_match('/^[一二三四五六七八九十千百万0-9０-９-−ー―]+/u', $_str, $_match) === 1) {
                                                $this->split_numeric_address($_match[0], $this->output);
                                                $ptn = $_match[0];
                                                $_tmp = explode($ptn, $_str, 2);
                                                if (!empty($_tmp[1])) {
                                                    $this->output['ic:ビル名'] = $_tmp[1];
                                                } else {
                                                    // ここがある？
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    // 町域検知失敗
                                    break;
                                }
                            }
                        }
                        // 都道府県
//                        if (preg_match('/(都|道|府|県)$/u', $_addsubstr) > 0) {
//                            $_tmpdata = null;
//                            $_tmppath = "";
//
//                            $this->output['ic:都道府県'] = $_addsubstr;
//
//                            // 東京都特別区
//                            if ($_addsubstr == '東京都' && (strpos($match[$_i + 1], '区') !== false)) {
//                                $this->output['ic:市区町村'] = $match[$_i + 1];
//                                $_tmppath = $this->datapath . 'kanto.json';
//                            } else {
//                                $_key = preg_replace('/(都|道|府|県)$/u', '', $_addsubstr);
//                                foreach ($this->prefs as $f => $_prefs) {
//                                    if (in_array($_key, $_prefs)) {
//                                        $_tmppath = $this->datapath . $f . '.json';
//                                    }
//                                }
//                                $_tmppath = $this->datapath .'.json';
////                                foreach ($this->prefs as $f => $_prefs) {
////                                    if (in_array($_key, $_prefs)) {
////                                        $_tmppath = $this->datapath . $f . '.json';
////                                    }
////                                }
//                            }
//                            // 町名データ読み込み
//                            if (!empty($_tmppath)){
//                                $this->datafile = realpath($_tmppath);
//                            }
//                            if (!empty($this->datafile) && file_exists($this->datafile)) {
//                                $_tmpdata = file_get_contents($this->datafile);
//                                if (!empty($_tmpdata)) {
//                                    $this->data = json_decode($_tmpdata, true);
//                                }
//                            }
//                        }
                        // 市
                        if (preg_match('/市$/u', $_addsubstr) > 0) {
                            $this->output['ic:市区町村'] = $_addsubstr;
                        }
                        // 町・村
                        if (preg_match('/(町|村)$/u', $_addsubstr) > 0) {
                            $this->output['ic:市区町村'] = $_addsubstr;
                        }
                        // 政令市から区を分離
                        if (preg_match('/区$/', $_addsubstr) > 0) {
                            foreach ($this->dcities as $_dcity) {
                                // 東京都は除外
                                if (strpos($_addsubstr, $_dcity) !== false) {
                                    $_match = array();
                                    $_ptn = '/(.{2,3}市)(.{1,3}区$)/u';
                                    preg_match($_ptn, $_addsubstr, $_match);
                                    if (!empty($_match)) {
                                        $this->output['ic:市区町村'] = $_match[1];
                                        $this->output['ic:区'] = $_match[2];
                                    } else {
                                        // 政令市市区分離失敗（存在しない市or区etc）
                                        break;
                                    }
                                } else {
                                    if ($this->output['ic:都道府県'] != '東京都') {
                                        // 存在しない政令市
                                        break;
                                    }
                                }
                            }
                        }
                        // 神奈川県 -> 神奈川県横浜市 -> 神奈川県横浜市金沢区 ... どんどん細分化
                        $_searchkey .= $_addsubstr;
                    }
                }
            }
        }
    }
    public function getOutput(){
        return $this->output;
    }
}