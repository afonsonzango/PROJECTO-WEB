<?php
echo "<h3>Diretor</h3>";
echo password_hash("diretor1", PASSWORD_ARGON2ID);

echo "<hr>";

echo "<h3>Secretaria</h3>";
echo password_hash("secretaria12", PASSWORD_ARGON2ID);
?>
