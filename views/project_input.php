<div class="container project" role="main">
	<form action="<?php echo $data['static_path']; ?>project" method="post" id="export_form">
		<?php if($data['action'] !== 'express'): ?>
			<div class="col-md-12  block">
				<h2 class="form_title">プロジェクト情報入力</h2>
				<div class="project_input">
					<div class="public_option">
						<div class="form-group">
							<label for="description-input">プロジェクトの内容がわかるような説明</label>
							<textarea id="description-input" class="form-control" rows="3" name="dct:description" ><?if(isset($data['project']['dct:description'])){ echo htmlspecialchars($data['project']['dct:description'], ENT_QUOTES, 'UTF-8'); } ?></textarea>
						</div>
						<div class="form-group">
							<label for="keywords-input">プロジェクトを検索するのにヒントになるようなキーワード（半角カンマ区切り）</label>
							<input type="text" id="keywords-input" class="form-control" name="eg:keyword" placeholder="例）保育園, 地理空間情報" value="<?if(isset($data['project']['eg:keyword'])){ if(is_array($data['project']['eg:keyword'])){ echo htmlspecialchars(implode(',' ,$data['project']['eg:keyword'])); }else{ echo htmlspecialchars($data['project']['eg:keyword'], ENT_QUOTES, 'UTF-8'); }; } ?>">
						</div>
						<div class="form-group">
							<label >プロジェクトタグ</label><br />
							<?php
							foreach((array)$data['tag']['tag'] as $key => $value):
								if(isset($data['project']['eg:tag']) && is_array($data['project']['eg:tag']) && in_array($value, $data['project']['eg:tag'])){
									$checked='checked="checked"';
								}else{
									$checked='';
								}
								?>
								<label class="checkbox-inline">
									<input type="checkbox" name="eg:tag[]" value="<?php echo $value; ?>" <?php echo $checked; ?>> <?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>
								</label>
							<?php endforeach; ?>
						</div>
						<div class="form-group">
							<label for="license-select">ライセンス</label>
							<select name="dct:license" class="form-control" id="license-select">
								<?
								if(!empty($data['license'])){
									foreach((array)$data['license'] as $key => $val):
										if(!empty($data['project']['dct:license']) && $key == $data['project']['dct:license']){
											$selected='selected="selected"';
										}else{
											$selected='';
										}
										?>
										<option value="<?php echo $key; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?></option>
									<?php endforeach;
								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<input type="hidden" name="_token" id="_token" value="<?php echo $data['_token'];?>" />
		<?php endif; ?>

		<?php if($data['action'] !== 'edit'): ?>
			<div class="col-md-12  block">
				<h2 class="form_title">出力データの形式</h2>
				<div class="form-group">
					<h3>XML</h3>
					<label class="radio-inline">
						<input type="radio" name="format" checked="checked" value="xml"> XML
					</label>
					<h3>RDF</h3>
					<label class="radio-inline">
						<input type="radio" name="format" value="rdfxml"> RDF/XML
					</label>
					<label class="radio-inline">
						<input type="radio" name="format" value="jsonld"> JSON-LD
					</label>
					<label class="radio-inline">
						<input type="radio" name="format" value="turtle"> Turtle Terse RDF Triple Language
					</label>
					<label class="radio-inline">
						<input type="radio" name="format" value="ntriples"> N-Triples
					</label>
				</div>
			</div>
		<?php endif; ?>

		<div class="btn_export">
			<input type="hidden" name="action" value="data" />
			<?php if($data['action'] !== 'edit'): ?>
				<button type="submit" class="btn btn-success btn_save">データをダウンロード</button>
			<?php else: ?>
				<button type="submit" class="btn btn-success btn_save">編集内容を保存する</button>
			<?php endif; ?>
		</div>
	</form>
</div> <!-- /container -->
