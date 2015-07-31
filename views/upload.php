<div class="container project" role="main">

	<div class="head_title">
		<img src="<?php echo $data['static_path']; ?>images/icon_cercle_g.png" alt="">
		<?php
		if(isset($data['action'])):
			if($data['action'] == 'fork'):  ?>
				<h1>Project from existing project</h1>
				<p>既存のプロジェクトからプロジェクトを作成します。</p>
			<?php elseif($data['action'] == 'edit'): ?>
				<h1>Edit the project</h1>
				<p>既存のプロジェクトを編集します。</p>
			<?php elseif($data['action'] == 'new'): ?>
				<h1>Start a New Project</h1>
				<p>ExcelやCSVデータから共通語彙基盤互換データを作成します。<br />
					既存のプロジェクトを参照することもできます。</p>
			<?php elseif($data['action'] == 'express'): ?>
				<h1>Convert the Data</h1>
				<p>既存のプロジェクトをそのまま参照して共通語彙基盤互換データを作成します。</p>
			<?php endif;
		endif; ?>
	</div>

	<div class="col-md-12 block">
		<h2 class="form_title">データ読み込み</h2>
		<p>.xls, .xlsx, .csvを選択してください。</p>
		<form action="<?php echo $data['static_path']; ?>data/upload" method="post" enctype="multipart/form-data">
			<input type="file" name="tableFile" class="input_file"/>
			<button type="submit" class="btn btn-primary">アップロード</button>
			<input type="hidden" name="action" value="upload" />
			<input type="hidden" name="_token" value="<?php echo $data['_token'];?>" />
		</form>
	</div>

	<?php if(($data['action'] == 'fork' || $data['action'] == 'edit') && isset($data['project'])): ?>
		<div class="col-md-12 block">
			<?php if($data['action'] == 'fork'):  ?>
				<h2>使用するプロジェクト</h2>
			<?php elseif($data['action'] == 'edit'): ?>
				<h2>編集するプロジェクト</h2>
			<?php endif ?>
			<?php include('elements/project_preview.php'); ?>
		</div>
	<?php endif; ?>

</div> <!-- /container -->
