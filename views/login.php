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

<a href="index.php?route=register">Регистрация</a>
</div>

<?php if (!empty($error)) : ?>
    <div style="color:red;">
        <?= $error ?>
    </div>
<?php endif; ?>

<?php require 'footer.php'; ?>
