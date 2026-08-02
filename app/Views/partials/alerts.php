<?php foreach (($_SESSION['flashes'] ?? []) as $type => $message): ?><div class="alert <?= e($type) ?>" role="alert"><?= e($message) ?></div><?php endforeach; unset($_SESSION['flashes']); ?>
