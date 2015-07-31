<div id="mapping_area" class="container" role="main">
	<div class="head_title">
		<img src="<?php echo $data['static_path']; ?>images/icon_cercle_g.png" alt="">
		<h1>Mapping</h1>
		<p>2次元表形式のデータに機械判読用の意味データをマッピングしていきます。</p>
	</div>
	<div class="row mapping_heading">
		<div class="col-md-12 ">
			<h2>プロジェクト：{{project['rdfs:label']}}</h2>
			<table class="table">
				<tbody>
				<tr>
					<th>Base URI</th>
					<td class="input_uri">
						<div class="input" v-if="uriEdit">
							<input type="text" v-model="project['eg:vocabulary']['eg:uri']" class="form-control" />
							<button type="button" class="btn" id="save-uri" v-on="click:saveUri">保存</button>
						</div>
						<div class="view" v-if="!uriEdit">
							<span id="append-uri">{{project['eg:vocabulary']['eg:uri']}}</span><img src="<?php echo $data['static_path']; ?>images/icon_pen_g.png" alt="ペン" id="edit_uri" v-on="click:toggleUriEdit">
						</div>
					</td>
				</tr>
				</tbody>
			</table>
		</div>
	</div>
	<div class="mapping_right">
		<div class="mapping_rightblock">
			<h4>1. ヘッダー行を指定</h4>
			<div class="data_preview_parent" >
				<div class="data_preview">
					<div class="table-responsive">
						<table class="table table-bordered">
							<tbody>
							<tr v-repeat="row : sheetData">
								<td v-repeat="col : row" v-on="click:selectIdCell" data-row="{{$parent.$index}}" data-col="{{$index}}" data-class="set_header" v-class="active:setDataPreviewActive(this.$el)">{{col}}</td>
							</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<div class="mapping_rightblock">
			<h4>2. ID付与ルールを指定</h4>
			<div class="select_id clearfix">
				<div class="col-md-3">
					<div class="radio-inline">
						<label><input type="radio" name="eg:namingRule" value="column"  v-model="project['eg:subject']['eg:namingRule']" >IDになる列を指定する</label>
					</div>
				</div>
				<div class="col-md-3">
					<div class="radio-inline">
						<label><input type="radio" name="eg:namingRule" value="increment"  v-model="project['eg:subject']['eg:namingRule']" >自動付番</label>
					</div>
				</div>
				<div class="col-md-6">
					<div class="input-group naming_constant">
						<div class="input-group-addon radio">
							<label><input type="radio" name="eg:namingRule" value="constant"  v-model="project['eg:subject']['eg:namingRule']" >固定値</label>
						</div>
						<input type="text" class="form-control" v-model="project['eg:subject']['eg:constant']" v-attr="disabled:this.project['eg:subject']['eg:namingRule'] !== 'constant' ? 'disabled' : ''">
					</div>
				</div>
			</div>
			<div class="data_preview_parent" v-if="project['eg:subject']['eg:namingRule'] == 'row' || project['eg:subject']['eg:namingRule'] == 'column'">
				<div class="data_preview">
					<div class="table-responsive">
						<table class="table table-bordered">
							<tbody>
							<tr v-repeat="row : sheetData">
								<td v-repeat="col : row" v-on="click:selectIdCell" data-row="{{$parent.$index}}" data-col="{{$index}}" data-class="set_idrule" v-class="active:setDataPreviewActive(this.$el)">{{col}}</td>
							</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<div class="mapping_rightblock">
			<h4>3. データタイプと構造を指定</h4>
			<div class="dropdown" id="input-a">
				<button class="btn btn-default" type="button" v-on="click:suggestionToggle">
					<span id="data-type">{{project['eg:class']}}</span>
					<span class="caret"></span>
				</button>
				<ul class="dropdown-menu parent">
					<?php foreach($data['prefix'] as $key => $prefix): ?>
						<li class="dropdown-submenu">
							<a tabindex="-1" href="#" data-vocabulary="<?php echo $key; ?>" v-on="mouseover:listSuggestion"><?php echo htmlspecialchars($prefix['name'], ENT_QUOTES, 'UTF-8'); ?></a>
							<ul class="dropdown-menu">
								<li v-repeat="suggestion.<?php echo $key; ?>">
									<a v-on="click:suggestionSet(this)">{{$value}}</a>
								</li>
							</ul>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<h3>{{project['eg:class']}}</h3>
			<table class="table" id="mapping_table">
				<tbody>
				<tr v-repeat="value : mapping" v-class="mapped:value['eg:targetType']">
					<td><button type="button" v-on="click:propertyDelete($index)"><i class="glyphicon glyphicon-minus"></i></button></td>
					<td class="m_table_bg" v-style="padding-left:value.level*3+'%',font-weight:value.level == 1?'bold':'normal'">{{value["eg:predicate"]}}</td>
					<td class="m-table_type">
						<button type="button" class="btn btn-sm btn-default modal_mapping" v-on="click:setMappingOpen($index)" v-if="!hasChildDom($index)">マッピング</button>
						<button type="button" class="btn btn-sm btn-default" v-on="click:propertyAddChild($index)" v-if="!hasChildDom($index) && hasChildProperty($index) && value.level<3">▼</button>
						<button type="button" class="btn btn-sm btn-default" v-on="click:propertyAddModalOpen($index)" v-if="value.level<3">+[]</button>
					</td>
				</tr>
				</tbody>
			</table>
			<button type="button" class="btn btn-primary" v-on="click:propertyAddModalOpen(-1)"><img src="<?php echo $data['static_path']; ?>images/icon_plus.png" alt="" /></button>
			<div id="addRowModal" class="modal fade">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
							<h4 class="modal-title">追加</h4>
						</div>
						<div class="modal-body">
							<div class="form-group">
								<label for="ic-name" class="control-label">クラスまたはプロパティ名:</label>
								<input type="text" class="form-control" v-model="icName" list="suggestions" autocomplete="on"/>
								<datalist id="suggestions">
									<option v-repeat="searchList | filterBy icName">
										{{$value}}
									</option>
								</datalist>
								<input type="hidden" v-model="addRowIndex" />
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-default"  data-dismiss="modal">キャンセル</button>
							<button type="button" class="btn btn-primary" v-on="click:propertyAdd">追加</button>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="save_mapping">
			<button type="button" class="btn btn-success btn_save" v-on="click:handleSave">保存</button>
		</div>
	</div>
	<div id="modalDefine" class="modal fade" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg modal_define" role="main">
			<div class="bg_space">
				<div class="modal_flash"></div>
				<div class="step">
					<h4>"<span id="target-propery">{{setMapping.targetName}}</span>"の値を指定</h4>
					<div class="select_property">
						<div class="radio-inline">
							<label><input type="radio" name="eg:targetType" value="column"  v-model="setMapping.targetType" >値になる列を指定する</label>
						</div>
						<div class="radio-inline">
							<label><input type="radio" name="eg:targetType" value="increment"  v-model="setMapping.targetType" >自動付番</label>
						</div>
						<div class="radio input-group">
							<div class="input-group-addon radio">
								<label><input type="radio" name="eg:targetType" value="constant"  v-model="setMapping.targetType" >固定値</label>
							</div>
							<input type="text" class="form-control" v-model="setMapping.targetTypeText" v-attr="disabled:setMapping.targetType !== 'constant' ? 'disabled' : ''">
						</div>
						<div class="radio input-group">
							<div class="input-group-addon radio">
								<label><input type="radio" name="eg:targetType" value="uri"  v-model="setMapping.targetType" >URIを入力する</label>
							</div>
							<input type="text" class="form-control" v-model="setMapping.targetTypeText" v-attr="disabled:setMapping.targetType !== 'uri' ? 'disabled' : ''">
						</div>
					</div>
					<div id="set_mapcel"  class="data_preview_parent" v-if="setMapping.targetType == 'column'">
						<div class="data_preview">
							<div class="table-responsive">
								<table class="table table-bordered">
									<tbody>
									<tr v-repeat="row : sheetData">
										<td v-repeat="col : row" v-on="click:selectIdCell" data-row="{{$parent.$index}}" data-col="{{$index}}" data-class="set_mapcel" v-class="active:setDataPreviewActive(this.$el)">{{col}}</td>
									</tr>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>

				<div class="step">
					<h4>値の型を指定</h4>
					<div class="radio-inline">
						<label><input type="radio" name="eg:propertyType" value="xsd:string"  v-model="setMapping.propertyType" >文字列</label>
					</div>
					<div class="radio-inline">
						<label><input type="radio" name="eg:propertyType" value="xsd:integer"  v-model="setMapping.propertyType" >整数</label>
					</div>
					<div class="radio-inline">
						<label><input type="radio" name="eg:propertyType" value="xsd:float"  v-model="setMapping.propertyType" >小数</label>
					</div>
					<div class="radio-inline">
						<label><input type="radio" name="eg:propertyType" value="xsd:date"  v-model="setMapping.propertyType" >日付</label>
					</div>
					<div class="radio-inline">
						<label><input type="radio" name="eg:propertyType" value="xsd:boolean"  v-model="setMapping.propertyType" >真偽</label>
					</div>
					<input type="text" class="form-control" v-model="setMapping.propertyType">
				</div>

				<div class="step">
					<h4>定型化コンポーネント</h4>
					<div class="radio-inline">
						<label><input type="radio" name="eg:APIComponent" value="" v-model="setMapping.APIComponent" >使用しない</label>
					</div>
					<?php
					if(isset($data['api_component']) && is_array($data['api_component'])):
						foreach($data['api_component'] as $key => $api):
							?>
							<div class="radio-inline">
								<label><input type="radio" name="eg:APIComponent" value="<?php echo $key; ?>"  v-model="setMapping.APIComponent" ><?php echo htmlspecialchars($api['name'], ENT_QUOTES, 'UTF-8'); ?></label>
							</div>
							<?php
						endforeach;
					endif; ?>
					<div class="radio input-group">
						<div class="input-group-addon radio">
							<label><input type="radio" disabled="true" name="eg:APIComponent" value="external" v-model="setMapping.APIComponent" >外部APIのURLを入力する</label>
						</div>
						<input type="text" disabled="true" class="form-control" v-model="setMapping.APIComponentURL" value="" placeholder="※本サイトではサポートしていません">
					</div>
				</div>
				<div class="btn_savenext">
					<button type="button" class="btn btn-default" data-dismiss="modal">キャンセル</button>
					<button type="button" class="btn btn-success btn_d01" v-on="click:saveMapping">保存</button>
				</div>
			</div>
		</div>
	</div>
	<input type="hidden" name="_token" id="_token" value="<?php echo $data['_token'];?>" />
</div>
