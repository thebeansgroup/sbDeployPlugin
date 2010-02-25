<?php if ($status == 'success'): ?>

<span class="success"><?php echo $messages['success'] ?></span>

<?php elseif ($status == 'error'): ?>

<span class="error"><?php echo $messages['error'] ?></span>

<?php endif ?>