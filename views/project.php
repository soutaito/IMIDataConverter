<div class="container project" role="main">
	<form action="<?php echo $data['static_path']; ?>project/mapping" method="post">

		<div class="project_input">
			<div class="form-group">
				<h3>プロジェクトタイトルを入力</h3>
				<input type="text" class="form-control" name="rdfs:label" value="<?php if(isset($data['project']) && isset($data['project']['rdfs:label'])){ echo htmlspecialchars($data['project']['rdfs:label'], ENT_QUOTES, 'UTF-8'); }?>" required>
			</div>
		</div>

		<?php if(!isset($data['project'])): ?>
			<div class="select_projects">
				<div class="project_recommend">
					<?php
					if(!empty($data['projects'])):
						?>
						<h4>おすすめのプロジェクト</h4>
						<?
						foreach($data['projects'] as $value) :
							?>
							<button class="btn btn-success" name="t" value="<?php echo $value['_id'][0];?>"><?php echo htmlspecialchars($value['_id']['rdfs:label'], ENT_QUOTES, 'UTF-8');?></button>
						<?php endforeach;
					endif;
					?>
				</div>
			</div>
			<div id="project_load"></div>
		<?php endif; ?>

		<h4>データプレビュー</h4>
		<div class="data_preview">
			<div class="table-responsive">
				<table class="table table-bordered">
					<tbody>
					<?php
					for($i=0; $i < $data['show_row_num']; $i++) :
						if(isset($data['sheetData'][$i])):
							?>
							<tr>
								<?php foreach( $data['sheetData'][$i] as $key => $cel) : ?>
									<td><?php echo htmlspecialchars($cel, ENT_QUOTES, 'UTF-8'); ?></td>
								<?php endforeach; ?>
							</tr>
						<?php endif;
					endfor; ?>
					</tbody>
				</table>
			</div>
		</div>

		<?php if(isset($data['project']) && isset($data['project']['_id'])): ?>
			<input type="hidden" name="t" value="<?php echo $data['project']['_id']; ?>" />
			<div>
				<?php if($data['action'] == 'fork'):  ?>
					<h3>使用するプロジェクト</h3>
				<?php elseif($data['action'] == 'edit'): ?>
					<h3>編集するプロジェクト</h3>
				<?php endif ?>
				<?php include('elements/project_preview.php'); ?>
			</div>
		<?php endif; ?>

		<div class="btn_savenext"><button type="submit" class="btn btn-success btn_save ">次へ</button></div>
		<input type="hidden" name="_token" value="<?php echo $data['_token'];?>" />
	</form>
</div> <!-- /container -->
