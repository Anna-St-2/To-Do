<?php require 'header.php'; ?>

<div class="auth">
<h2>Вход</h2>

<?php if (!empty($error)): ?>
<p class="error"><?= $error ?></p> <!-- @SECURITY_MODULE -->
<?php endif; ?>

<form method="POST">
<input name="login" placeholder="Логин или Email">
<input type="password" name="password" placeholder="Пароль">
<button>Войти</button>
</form>

<a href="/register">Регистрация</a>
</div>

<?php require 'footer.php'; ?>