<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Login</title>
  <link rel="icon" type="image/x-icon" href="favicon111.ico">

  <!-- boxicons -->
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap');
    *{ box-sizing:border-box; margin:0; padding:0; font-family:'Poppins',sans-serif }

    body{
      display:flex;
      justify-content:center;
      align-items:center;
      min-height:100vh;
      background:url('../assets/images/cooking11.jpg') no-repeat;
      background-size: cover;
      background-position: center;
      padding: 20px;
    }
    .wrapper{
      width:380px;
      max-width:100%;
      padding:30px;
      background:rgba(255,255,255,0.95);
      box-shadow:0 10px 40px rgba(0,0,0,0.35);
      border-radius:20px;
      display:flex;
      flex-direction:column;
    }
    h1{
      text-align:center;
      color:#1f2937;
      margin-bottom:20px;
      font-size: 1.8em;
    }
    .input-box{
      position:relative;
      margin-bottom:12px;
    }
    .input-box input {
      width: 100%;
      height: 44px;
      padding: 0 12px;
      border: 1px solid #d1d5db;
      border-radius: 8px;
      font-size: 0.95rem;
      outline: none;
      background: transparent;
      color: black;
      padding-left: 40px;
    }
    .input-box input::placeholder { color: grey; }
    .input-box i {
      position: absolute;
      left: 10px;
      top: 12px;
      color: black;
      font-size: 1.2em;
    }
    .btn {
      width: 100%;
      height: 44px;
      background: hsl(158, 82%, 15%);
      box-shadow: 0 2px 10px rgba(0, 0, 0, .4);
      font-size: 1em;
      color: #fff;
      font-weight: 500;
      cursor: pointer;
      border-radius: 8px;
      border: none;
      outline: none;
      transition: .15s;
      display: inline-flex;
      justify-content: center;
      align-items: center;
      gap: 8px;
    }
    .btn:hover { background: white; color: hsl(158, 82%, 15%); border: 1px solid hsl(158, 82%, 15%); }
    .register-link { margin-top: 14px; text-align: center; }
    .register-link a { color: hsl(158, 82%, 15%); text-decoration: none; font-weight: 600; }

    .error-message { color: #b91c1c; font-size: 0.85rem; display:none; margin-top:6px; }
    .field-error { border-color: #b91c1c !important; }

    /* Server message */
    .server-message {
      margin-top:10px;
      font-size:0.95rem;
      display:none;
      padding:8px 10px;
      border-radius:6px;
    }
    .server-message.error { background: #fee2e2; color:#b91c1c; border:1px solid #fecaca; display:none; }
    .server-message.success { background: #ecfdf5; color:#065f46; border:1px solid #bbf7d0; display:none; }

    /* Loader */
    .loader {
      border: 3px solid #f3f3f3;
      border-radius: 50%;
      border-top: 3px solid #16a34a;
      width: 16px;
      height: 16px;
      animation: spin 1s linear infinite;
      display: none;
    }
    @keyframes spin { 100% { transform: rotate(360deg); } }

    /* small screens */
    @media (max-width:420px){
      .wrapper{ padding:18px; width:100%;}
      h1{ font-size:1.4em; }
    }
  </style>
</head>
<body>
  <div class="wrapper" role="main" aria-labelledby="loginHeading">
    <h1 id="loginHeading">Login</h1>

    <!-- NOTE: action is set as a fallback for non-JS users. -->
    <form id="loginForm" action="../actions/login_customer_action.php" method="post" novalidate>
      <div class="input-box">
        <input type="email" id="email" name="email" placeholder="Email" required aria-describedby="emailError">
        <i class='bx bxs-envelope' aria-hidden="true"></i>
        <div id="emailError" class="error-message" role="alert">Please use your ashesi.edu.gh email address.</div>
      </div>

      <div class="input-box">
        <input type="password" id="password" name="password" placeholder="Password" required aria-describedby="passwordError">
        <i class='bx bxs-lock-alt' aria-hidden="true"></i>
        <div id="passwordError" class="error-message" role="alert">
          Password must be at least 8 characters, include 1 uppercase letter, at least 3 digits, and 1 special character.
        </div>
      </div>

      <button type="submit" class="btn" id="submitBtn">
        <span id="btnText">Login</span>
        <span class="loader" id="loader" aria-hidden="true"></span>
      </button>

      <div class="server-message error" id="serverError" role="alert"></div>
      <div class="server-message success" id="serverSuccess" role="status"></div>

      <div class="register-link">
        <p>Don't have an account? <a href="register.php">Sign Up</a></p>
      </div>
    </form>
  </div>

  <script>
    (function () {
      // Elements
      const form = document.getElementById('loginForm');
      const emailEl = document.getElementById('email');
      const passEl = document.getElementById('password');
      const emailError = document.getElementById('emailError');
      const passError = document.getElementById('passwordError');
      const serverError = document.getElementById('serverError');
      const serverSuccess = document.getElementById('serverSuccess');
      const loader = document.getElementById('loader');
      const submitBtn = document.getElementById('submitBtn');
      const btnText = document.getElementById('btnText');

      // Validation patterns (matches your earlier requirements)
      const emailPattern = /^[a-zA-Z0-9._%+-]+@ashesi\.edu\.gh$/;
      const passwordPattern = /^(?=.*[A-Z])(?=(.*\d){3})(?=.*[@#$%^&+=!]).{8,}$/;

      function showFieldError(el, msgEl) {
        el.classList.add('field-error');
        msgEl.style.display = 'block';
      }
      function hideFieldError(el, msgEl) {
        el.classList.remove('field-error');
        msgEl.style.display = 'none';
      }
      function showServerMessage(el, text) {
        el.textContent = text;
        el.style.display = 'block';
      }
      function hideServerMessages() {
        serverError.style.display = 'none';
        serverSuccess.style.display = 'none';
      }

      function setLoading(isLoading) {
        if (isLoading) {
          loader.style.display = 'inline-block';
          btnText.textContent = 'Logging in...';
          submitBtn.disabled = true;
        } else {
          loader.style.display = 'none';
          btnText.textContent = 'Login';
          submitBtn.disabled = false;
        }
      }

      // Client-side validation: returns boolean
      function validateInputs() {
        let ok = true;
        hideFieldError(emailEl, emailError);
        hideFieldError(passEl, passError);
        hideServerMessages();

        if (!emailPattern.test(emailEl.value.trim())) {
          showFieldError(emailEl, emailError);
          ok = false;
        }
        if (!passwordPattern.test(passEl.value)) {
          showFieldError(passEl, passError);
          ok = false;
        }
        return ok;
      }

      // If JS is enabled, intercept submit and use AJAX
      form.addEventListener('submit', function (e) {
        e.preventDefault(); // we'll handle submit
        if (!validateInputs()) {
          return;
        }

        setLoading(true);

        // Prepare form data
        const data = new FormData();
        data.append('email', emailEl.value.trim());
        data.append('password', passEl.value);

        // Use fetch to POST to your action that returns JSON
        fetch(form.action, {
          method: 'POST',
          body: data,
          credentials: 'same-origin' // include cookies for session
        })
        .then(async (res) => {
          setLoading(false);

          // Try parse JSON
          let json;
          try {
            json = await res.json();
          } catch (err) {
            showServerMessage(serverError, 'Server returned invalid response. Try again.');
            return;
          }

          if (!res.ok) {
            // HTTP error (500, 404, etc)
            const msg = json && json.message ? json.message : 'Server error. Try again.';
            showServerMessage(serverError, msg);
            return;
          }

          // Expecting your action to return { status: 'success'|'error', message: '', user: {...} }
          if (json.status === 'success') {
            showServerMessage(serverSuccess, json.message || 'Login successful');

            // Redirect to dashboard or homepage after short delay so user sees success (change URL as needed)
            setTimeout(function () {
              window.location.href = '../index.php'; // <-- change this to your real landing page
            }, 800);
          } else {
            // show message from server
            showServerMessage(serverError, json.message || 'Invalid credentials');
          }
        })
        .catch((err) => {
          setLoading(false);
          console.error('Network error:', err);
          showServerMessage(serverError, 'Network error. Check your connection and try again.');
        });
      });

      // Clear field errors on input
      emailEl.addEventListener('input', () => hideFieldError(emailEl, emailError));
      passEl.addEventListener('input', () => hideFieldError(passEl, passError));
    })();
  </script>
</body>
</html>
