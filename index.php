<?php
require_once 'settings/core.php';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Landing Page</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet"/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/landing1.css" />
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel = "icon" type= "image/x-icon" href="favicon111.ico">
  </head>

  <body>
    <div class="banner">
      <div class="navbar">
        <ul>
          <li><a href="#">About</a></li>

          <?php if (!isLoggedIn()): ?>
            <li><a href="view/register.php">Register</a></li>
            <li><a href="view/login.php">Login</a></li>

          <?php else: ?>
            <?php if (isAdmin()): ?>
              <li><a href="actions/logout.php">Logout</a></li>
              <li><a href="admin/category.php">Category</a></li>
              <li><a href="admin/brand.php">Brand</a></li>
            <?php else: ?>
              <li><a href="actions/logout.php">Logout</a></li>
            <?php endif; ?>
          <?php endif; ?>

        </ul>
      </div>
      <div class="content">
        <h1>Welcome To Z's Page</h1>
        <p>Dress For Less!<br>Book appointments, shop effectively, and access helpful resources,all in one place.</p>
        <div>
          <button type="button">
            <span></span>
            <a href="view/register.php" class="link-btn">GET STARTED</a>
          </button>
        </div>
      </div>
    </div>
  </body>
</html>