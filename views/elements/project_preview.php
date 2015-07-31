<?
$project = $data['project'];
?>
<div class="project_preview">
	<h3>
		<?
		if(!empty($data['on_new_project'])):
			?>
			<a href="<?php echo $data['static_path']; ?>project/<?php echo $project['_id']; ?>"><?php echo htmlspecialchars($project['rdfs:label'], ENT_QUOTES, 'UTF-8'); ?></a>
		<?php else: ?>
			<?php echo htmlspecialchars($project['rdfs:label'], ENT_QUOTES, 'UTF-8'); ?>
		<?php endif; ?>
	</h3>
	<table class="table">
		<tbody>
		<tr>
			<th>作者</th>
			<td>
				<?php
				if(!empty($project['dct:creator'])){
					echo htmlspecialchars($project['dct:creator'], ENT_QUOTES, 'UTF-8');
				}
				?>
			</td>
		</tr>
		<tr>
			<th>説明文</th>
			<td>
				<?php
				if(!empty($project['dct:description'])){
					echo shorten(htmlspecialchars($project['dct:description'], ENT_QUOTES, 'UTF-8'), 100);
				}
				?>
			</td>
		</tr>
		<tr>
			<th>作成日</th>
			<td><?php echo getYmd($project['dct:created']); ?></td>
		</tr>
		<tr>
			<th>ライセンス</th>
			<td>
				<?php if(!empty($project['dct:license'])): ?>
				<span class="label label-default">
					<a href="<?php echo $data['static_path']; ?>?license=<?php echo urlencode($project['dct:license']); ?>"><?php echo htmlspecialchars($project['dct:license'], ENT_QUOTES, 'UTF-8'); ?></a>
				</span>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th>タグ</th>
			<td>
				<?
				if(isset($project['eg:tag'])):
					foreach((array)$project['eg:tag'] as $v): ?>
						<span class="label label-info"><a href="<?php echo $data['static_path']; ?>?tag=<?php echo urlencode($v); ?>"><?php echo htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); ?></a></span>
					<?php endforeach;
				endif;
				?>
			</td>
		</tr>
		<tr>
			<th>キーワード</th>
			<td>
				<?
				if(isset($project['eg:keyword'])):
					foreach((array)$project['eg:keyword'] as $v): ?>
						<span class="label label-info"><a href="<?php echo $data['static_path']; ?>?keyword=<?php echo urlencode($v); ?>"><?php echo htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); ?></a></span>
					<?php endforeach;
				endif;
				?>
			</td>
		</tr>
		<tr>
			<th>ヘッダラベル</th>
			<td>
				<?
				if(isset($project['eg:headerLabel'])):
					foreach((array)$project['eg:headerLabel'] as $v):; ?>
						<span class="label label-default"><?php echo htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); ?></span>
					<?php endforeach;
				endif;
				?>
			</td>
		</tr>
		</tbody>
	</table>
	<?
	if(!empty($data['on_new_project'])):
		?>
		<button class="btn btn-info fork" type="submit" name="fork" value="<?php echo $project['_id']; ?>">
			このプロジェクトを複製する
		</button>
	<?php endif; ?>
</div>
