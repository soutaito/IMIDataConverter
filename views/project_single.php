<div class="project_single" role="main">
	<?php
	$project = $data['project'];
	?>
	<div class="sub_header clearfix">
		<div class="container">
			<h3>
				<?
				if(!empty($data['is_new_project'])):
					?>
					<a href="<?php echo $data['static_path']; ?>project/<?php echo $project['_id']; ?>"><?php echo htmlspecialchars($project['rdfs:label']); ?></a>
				<?php else: ?>
					<?php echo htmlspecialchars($project['rdfs:label']); ?>
				<?php endif; ?>
			</h3>
			<ul class="subhdr_btn-list">
				<?php if(!empty($_SESSION['user'])): ?>
					<li class="subhdr_btn">
						<a href="<?php echo $data['static_path']; ?>data/upload/fork/<?php echo $data['project']['_id']; ?>" title="別のプロジェクトとして複製">
							<span class="subhdr_btn-add"></span>
						</a>
					</li>
				<?php endif; ?>
				<?php if($data['is_my_project']): ?>
					<li class="subhdr_btn">
						<a href="<?php echo $data['static_path']; ?>data/upload/edit/<?php echo $data['project']['_id']; ?>" title="編集">
							<span class="subhdr_btn-edit"></span>
						</a>
					</li>
					<li class="subhdr_btn">
						<a href="<?php echo $data['static_path']; ?>project/remove/<?php echo $data['project']['_id']; ?>" title="削除" id="delete_project">
							<span class="subhdr_btn-del"></span>
						</a>
					</li>
				<?php endif; ?>
			</ul>
		</div>
	</div>
	<div class="container">
		<div class="project_container clearfix">
			<div class="project_col_info col-lg-9">
				<?php
				if (!empty($project['dct:description'])):
					?>
					<p><?php echo htmlspecialchars($project['dct:description'], ENT_QUOTES, 'UTF-8'); ?></p>
					<?php
				endif;
				?>
				<ul class="list-inline meta">
					<li><a class="label label-success" href="<?php echo $data['static_path']; ?>?creator=<?php echo urlencode($project['dct:creator']); ?>"><?php echo htmlspecialchars($project['dct:creator'], ENT_QUOTES, 'UTF-8'); ?></a></li>
					<?php
					if(isset($project['eg:tag'])):
						foreach((array)$project['eg:tag'] as $tag): ?>
							<li><a class="label label-info" href="<?php echo $data['static_path']; ?>?tag=<?php echo urlencode($tag); ?>"><?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?></a></li>
						<?php endforeach;
					endif;
					?>
				</ul>
				<?php
				if (!empty($project['dct:license'])):
					?>
					<p class="dct_rights">
						<a href="<?php echo $data['static_path']; ?>?license=<?php echo urlencode($project['dct:license']); ?>">
							<?php echo getCCicons($project['dct:license']); ?>
						</a>
					</p>
					<?php
				endif;

				if (!empty($project['dct:created'])):
					?>
					<p class="dct_created">
						作成日: <?php echo getYmd($project['dct:created']); ?>
					</p>
					<?php
				endif;
				?>
                <?
                if(isset($project['eg:headerLabel'])):
                ?>
                    <p>ヘッダラベル</p>
                <?
                    foreach((array)$project['eg:headerLabel'] as $v):; ?>
                        <span class="label label-default"><?php echo htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endforeach;
                endif;
                ?>
			</div>
            <?
            if(isset($project['eg:headerLabel'])):
            ?>
			<div class="project_col_ctrl col-lg-3">
				<div class="btn-group btn-block download">
					<button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
						<span class="glyphicon glyphicon-download-alt"></span>
						ダウンロード
					</button>
					<ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu1">
						<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo $data['static_path']; ?>project/download/<?php echo $data['project']['_id']; ?>?type=csv">CSV</a></li>
						<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo $data['static_path']; ?>project/download/<?php echo $data['project']['_id']; ?>?type=xlsx">XLSX</a></li>
					</ul>
				</div>
			</div>
            <?
            endif;
            ?>
		</div>
	</div>

	<div class="action clearfix container">
		<div class="dropdown usage">
			<button class="btn btn-success btn-lg dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-expanded="true">
				このプロジェクトを使用する
				<span class="caret"></span>
			</button>
			<ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu1">
				<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo $data['static_path']; ?>data/upload/express/<?php echo $data['project']['_id']; ?>">そのまま使用</a></li>
				<?php if(!empty($_SESSION['user'])): ?>
					<li role="presentation"><a role="menuitem" tabindex="-1" href="<?php echo $data['static_path']; ?>data/upload/fork/<?php echo $data['project']['_id']; ?>">別のプロジェクトとして複製</a></li>
				<?php endif; ?>
			</ul>
		</div>
	</div>

</div> <!-- /container -->

