<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title><?= esc($subject) ?></title>
  <style>
    body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
    .container { max-width: 600px; margin: auto; background: #fff; padding: 30px; border-radius: 10px; }
    .logo { text-align: center; margin-bottom: 20px; }
    .footer { font-size: 12px; color: #999; text-align: center; margin-top: 30px; }
  </style>
</head>
<body>
  <div class="container">
    <?= $this->include('email/partials/header') ?>
    <?= $this->renderSection('content') ?>
    <?= $this->include('email/partials/footer') ?>
  </div>
</body>
</html>
