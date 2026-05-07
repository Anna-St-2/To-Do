<?php require 'header.php'; ?>

<div class="auth">
<h2>Регистрация</h2>

<p class="error"><?= $error ?? '' ?></p> <!-- @SECURITY_MODULE -->

<form method="POST">
<input name="username" placeholder="Логин">
<input name="email" placeholder="Email">
<input type="password" name="password" placeholder="Пароль">
<input type="password" name="confirm" placeholder="Повтор">
<button>Зарегистрироваться</button>
</form>

<a href="/login">Войти</a>
</div>

<?php require 'footer.php'; ?>