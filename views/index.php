<div role="main">
	<div class="search_bar navbar-inverse">
		<div class="container">
			<div class="row">
				<div class="search_box col-md-7">
					<form action="<?php echo $data['static_path']; ?>" method="get" class="row " role="search">
						<div class="form-group  col-md-10">
							<input type="text" class="form-control" placeholder="プロジェクトを探す" name="keyword" value="<?php echo htmlspecialchars($data['keyword'], ENT_QUOTES, 'UTF-8');?>">
						</div>
						<div class=" col-md-2">
							<button class="btn btn-success" type="submit">検索</button>
						</div>
					</form>
				</div>
				<div class="col-md-5">
					<ul class="nav navbar-nav filter_menu">
						<li class="right">
							<a id="drop_catselect" class="dropdown-toggle" data-toggle="dropdown" href="#">
								タグを選択
								<span class="caret"></span>
							</a>
							<ul class="dropdown-menu" aria-labelledby="drop_catselect">
								<?php foreach($data['list']['tag'] as $key => $value): ?>
									<li><a href="<?php echo $data['static_path']; ?>?tag=<?php echo urlencode($value); ?>"><?php echo $value?>(<?php echo htmlspecialchars($data['list']['tag_count'][$key], ENT_QUOTES, 'UTF-8'); ?>)</a></li>
								<?php  endforeach; ?>
							</ul>
						</li>
						<li class="right">
							<a id="drop_userselect" class="dropdown-toggle" data-toggle="dropdown" href="#">
								ユーザーを選択
								<span class="caret"></span>
							</a>
							<ul class="dropdown-menu" aria-labelledby="drop_userselect">
								<?php foreach($data['list']['creator'] as $key => $value): ?>
									<li><a href="<?php echo $data['static_path']; ?>?creator=<?php echo urlencode($value); ?>"><?php echo $value?>(<?php echo htmlspecialchars($data['list']['creator_count'][$key], ENT_QUOTES, 'UTF-8'); ?>)</a></li>
								<?php  endforeach; ?>
							</ul>
						</li>
					</ul>
				</div>
			</div>
		</div>
	</div>
	<div class="project_list">
		<div class="sub_header clearfix">
			<div class="container">
				<div class="row">
					<div class="col-md-12">
						<?php
							?>
							<h2 class="filter">プロジェクト一覧</h2>
							<?php
						if(!empty($data['creator']) && is_string($data['creator'])):
							?>
							<h2 class="filter">ユーザー「<?php echo htmlspecialchars($data['creator'], ENT_QUOTES, 'UTF-8'); ?>」の作成したプロジェクト</h2>
							<?php
						endif;
						if(!empty($data['tag']) && is_string($data['tag'])):
							?>
							<h2 class="filter">タグ「<?php echo htmlspecialchars($data['tag'], ENT_QUOTES, 'UTF-8'); ?>」での絞り込み結果</h2>
							<?php
						endif;
						if(!empty($data['keyword']) && is_string($data['keyword'])):
							?>
							<h2 class="filter">キーワード「<?php echo htmlspecialchars($data['keyword'], ENT_QUOTES, 'UTF-8'); ?>」での検索結果</h2>
							<?php
						endif;
						if(!empty($data['license']) && is_string($data['license'])):
							?>
							<h2 class="filter">ライセンス「<?php echo htmlspecialchars($data['license'], ENT_QUOTES, 'UTF-8'); ?>」で絞り込み結果</h2>
							<?php
						endif;
						?>
					</div>
				</div>
			</div>
		</div>
		<div class="container">
			<div class="row">
				<?php
				if(!empty($data['results'])):
				foreach((array)$data['results'] as $key => $value): ?>
					<div class="project_item clearfix">
						<div class="col-md-10">
							<h4>
								<a href="<?php echo $data['static_path']; ?>project/<?php echo $value['_id']; ?>"><?php echo htmlspecialchars($value['rdfs:label'], ENT_QUOTES, 'UTF-8'); ?></a>
							</h4>
							<?php
							if (!empty($value['dct:description'])):
								?>
								<p><?php echo htmlspecialchars($value['dct:description'], ENT_QUOTES, 'UTF-8'); ?></p>
								<?php
							endif;
							?>
							<ul class="list-inline meta">
								<?php
								if (!empty($value['dct:creator'])):
								?>
								<li><a class="label label-success" href="<?php echo $data['static_path']; ?>?creator=<?php echo urlencode($value['dct:creator']); ?>"><?php echo htmlspecialchars($value['dct:creator'], ENT_QUOTES, 'UTF-8'); ?></a></li>
									<?php
								endif;
								?>
								<?php
								if(isset($value['eg:tag'])):
								foreach((array)$value['eg:tag'] as $tag):?>
									<li><a class="label label-info" href="<?php echo $data['static_path']; ?>?tag=<?php echo urlencode($tag); ?>"><?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?></a></li>
								<?php endforeach;
								endif;
								?>
							</ul>
							<?php
							if (!empty($value['dct:license'])):
								?>
								<p class="dct_rights">
									<a href="<?php echo $data['static_path']; ?>?license=<?php echo urlencode($value['dct:license']); ?>">
										<?php echo getCCicons($value['dct:license']);  ?>
									</a>
								</p>
								<?php
							endif;

							if (!empty($value['dct:created'])):
								?>
								<p class="dct_created">
										作成日: <?php echo getYmd($value['dct:created']); ?>
								</p>
								<?php
							endif;
							?>
						</div>
                        <?
                        if(isset($value['eg:headerLabel'])):
                        ?>
						<div class="col-md-2">
							<div class="btn-group btn-block download">
								<button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
									<span class="glyphicon glyphicon-download-alt"></span>
									ダウンロード
								</button>
								<ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu1">
									<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo $data['static_path']; ?>project/download/<?php echo $value['_id']; ?>?type=csv">CSV</a></li>
									<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo $data['static_path']; ?>project/download/<?php echo $value['_id']; ?>?type=xlsx">XLSX</a></li>
								</ul>
							</div>
						</div>
                        <? endif; ?>
					</div>
				<?php endforeach;
					elseif(!empty($data['list'])):
				?>
					<h5 class="no_results">条件に一致するプロジェクトはありませんでした。</h5>
					<?php
				endif;
				?>
				<?php
				pagination($data['count'], $data['path'] , $data['perpage']);
				?>
			</div>
		</div>
	</div>
</div>

