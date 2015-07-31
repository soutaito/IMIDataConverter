<div class="container " role="main">
	<div class="head_title col-md-12">
		<h1>Login</h1>
	</div>

	<form class="form_signin" action="<?php echo $data['static_path']; ?>login" method="post">
		<label for="inputEmail">メールアドレス</label>
		<input type="email" id="email" class="form-control" placeholder="" name="email" required autofocus>
		<label for="inputPassword" >パスワード</label>
		<input type="password" id="inputPassword" class="form-control" placeholder="" name="password" autocomplete="off" required>
		<button class="btn btn-lg btn-primary btn-block" type="submit">ログイン</button>
	</form>
</div> <!-- /container -->
