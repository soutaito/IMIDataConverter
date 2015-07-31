<?php
$app = \Slim\Slim::getInstance();
$req = $app->request;
$path = $req->getPath();
$path = str_replace($data['static_path'], '/', $path);
?>
<!DOCTYPE html>
<html>
<head lang="ja">
	<meta charset="UTF-8">
	<title><?php echo htmlspecialchars($app->config('tool_name'), ENT_QUOTES, 'UTF-8'); ?></title>
	<link rel="icon" href="<?php echo $data['static_path']; ?>favicon.ico">
	<link href="//cdnjs.cloudflare.com/ajax/libs/cc-icons/1.2.1/css/cc-icons.min.css" rel="stylesheet">
	<link rel="stylesheet" href="<?php echo $data['static_path']; ?>css/bootstrap.min.css">
	<link rel="stylesheet" href="<?php echo $data['static_path']; ?>css/style.css">
	<script src="<?php echo $data['static_path']; ?>js/jquery.min.js"></script>
	<script src="<?php echo $data['static_path']; ?>js/vue.min.js"></script>
	<script src="<?php echo $data['static_path']; ?>js/bootstrap.min.js"></script>
	<script type="text/javascript">
		var productPath = "<?php echo $data['static_path']; ?>";
	</script>
</head>
<body role="document" class="<?php echo  str_replace('/','_',ltrim($path,'/')); ?>">
<nav class="navbar navbar-inverse navbar-fixed-top">
	<div class="container">
		<div class="navbar-header">
			<a class="navbar-brand" href="<?php echo $data['static_path']; ?>">
				<img src="<?php echo $data['static_path']; ?>images/imi_logo.png" alt="" height="25">
			</a>
		</div>
		<div class="navbar-collapse collapse">
			<ul class="nav navbar-nav header_menu">
				<?php if(!empty($_SESSION['user'])): ?>
					<li class="right ">
						<a id="drop_usermenu" class="dropdown-toggle" data-toggle="dropdown" href="#">
							<?php echo htmlspecialchars($_SESSION['user']['username'], ENT_QUOTES, 'UTF-8');?>
							<span class="caret"></span>
						</a>
						<ul class="dropdown-menu" aria-labelledby="drop_usermenu">
							<li>
								<a href="<?php echo $data['static_path']; ?>?creator=<?php echo urlencode($_SESSION['user']['username']);?>">
									プロジェクト一覧
								</a>
							</li>
							<li>
								<a href="<?php echo $data['static_path']; ?>logout">
									ログアウト
								</a>
							</li>
						</ul>
					</li>
				<?php else: ?>
					<li class="right <?php if($path == '/login'){ echo 'active'; } ?>">
						<a href="<?php echo $data['static_path']; ?>login">
							ログイン
						</a>
					</li>
				<?php endif; ?>
				<li class="<?php if($path == '/project'){ echo 'active'; } ?> right">
					<a href="<?php echo $data['static_path']; ?>data/upload">
						プロジェクト作成
					</a>
				</li>
			</ul>
		</div>
	</div>
</nav>
<div class="wrapper">
	<div class="flash">
		<?php
		if(isset($flash)):
			foreach($flash as $key => $f):
				?>
				<p class="flash <?php echo $key; ?>"><?php echo htmlspecialchars($f, ENT_QUOTES, 'UTF-8'); ?></p>
				<?php
			endforeach;
		endif;
		?>
	</div>
	<?php
	if(isset($yield)){
		echo $yield;
	}
	?>
</div><!-- .wrapper -->
<?php if(strpos($path, '/project') === 0): ?>
	<script src="<?php echo $data['static_path']; ?>js/project.js"></script>
<?php endif; ?>
<?php if($path == '/project/mapping'): ?>
	<script src="<?php echo $data['static_path']; ?>js/mapping.js"></script>
<?php endif; ?>
<?php if($path == '/complete'): ?>
	<script src="<?php echo $data['static_path']; ?>js/complete.js"></script>
<?php endif; ?>
</body>
</html>
