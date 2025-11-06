<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Sign Up Form</title>
  <link rel="icon" type="image/x-icon" href="favicon111.ico">

  <!-- boxicons -->
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

  <!-- intl-tel-input CSS (phone input) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/18.1.1/css/intlTelInput.css"/>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap');
    
*{
    box-sizing:border-box;
    margin:0;
    padding:0;
    font-family:'Poppins',sans-serif
}
        
body{
    display:flex;
    justify-content:center;
    align-items:center;
    min-height:100vh;
    background:url('../assets/images/cooking11.jpg') no-repeat;
    background-size: cover;
    background-position: center;
    justify-content: center;
    align-items: center;
}
.wrapper{
    width:420px;
    display: flex;
    padding:30px;
    background:rgba(255,255,255,0.95);
    box-shadow:0 10px 40px rgba(0,0,0,0.35);
    border-radius:20px;
    flex-direction: column;
}
        
h1{
    text-align:center;
    color:#1f2937;
    margin-bottom:0.1px;
    font-size: 1.8em;
}
    
.input-box{
    position:relative;
    margin:6px 
}
        
.input-box input,
.input-box select {
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
.input-box input::placeholder {
    color: grey;
}

.input-box i {
    position: absolute;
    display: block;
    left: 10px;
    top:25px;
    color: black;
    font-size: 1.2em;
    line-height: 45px;
}

label {
    display: block;
    font-size: 0.8rem;
    color: #374151;
    margin-bottom: 6px;
}

.btn {
    position: relative;
    width: 100%;
    height: 40px;
    background: hsl(158, 82%, 15%);
    box-shadow: 0 2px 10px rgba(0, 0, 0, .4);
    font-size: 1em;
    color: #fff;
    font-weight: 500;
    cursor: pointer;
    border-radius: 5px;
    border: none;
    outline: none;
    transition: .5s;
}

.btn:hover {
    background: white;
    color: hsl(158, 82%, 15%);
}

.register-link {
    margin-top: 12px;
    text-align: center;
}

.register-link a {
    color: hsl(158, 82%, 15%);
    text-decoration: none;
    font-weight: 600;
}

.error-message {
    color: #b91c1c;
    font-size: 0.85rem;
    display: none;
    margin-top: 6px;
}

/* Adjust intl-tel-input inside container */
.iti {
    width: 100%;
}

.phone-wrap {
    position: relative;
}
  </style>
</head>
<body>
  <div class="wrapper">
    <form id="registerForm" action="../actions/register_customer_action.php" method="post" novalidate>
      <h1>Sign Up</h1>

      <!-- Single Name field -->
      <div class="input-box">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" placeholder="Full Name" required>
        <i class='bx bxs-user'></i>
      </div>

      <div class="input-box">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="user@ashesi.edu.gh" required>
        <div class="error-message" id="emailError">Invalid email. Use your Ashesi email (e.g., user@ashesi.edu.gh).</div>
        <i class='bx bxs-envelope'></i> 
      </div>

      <div class="input-box">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Password" required>
        <div class="error-message" id="passwordError">Password must be at least 8 chars, 1 uppercase, 3 digits, and 1 special char.</div>
        <i class='bx bxs-lock-alt' ></i>
      </div>

      <div class="input-box">
        <label for="confirmPassword">Confirm password</label>
        <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm Password" required>
        <div class="error-message" id="confirmPasswordError">Passwords do not match.</div>
        <i class='bx bxs-lock-alt' ></i>
      </div>

      <!-- Country (populated from intl-tel-input's country data) -->
      <div class="input-box">
        <label for="country">Country</label>
        <select id="country" name="country" required>
          <option value="">Select country</option>
        </select>
        <i class='bx bx-globe'></i>
      </div>

      <div class="input-box">
        <label for="city">City</label>
        <select id="city" name="city" required>
          <option value="">Select city</option>
        </select>
        <i class='bx bx-buildings'></i>
      </div>

      <!-- Phone using intl-tel-input -->
      <div class="input-box phone-wrap">
        <label for="phone_number">Phone number</label>
        <input id="phone_number" name="phone_display" type="tel" placeholder="Enter phone number" required>
        <div class="error-message" id="phoneError">Please enter a valid phone number.</div>
      </div>

      <!-- Hidden fields for server (names match what your PHP expects) -->
      <input type="hidden" id="fullPhone" name="full_phone_e164">
      <input type="hidden" id="countryCodeInput" name="country_code">
      <input type="hidden" id="phoneLocalInput" name="phone_number">
      <!-- Role is automatically set to 2 (Customer) at SQL level -->
      <input type="hidden" name="role" value="2">

      <button type="submit" class="btn">
        <span id="registerBtnText">Register</span>
        <span class="loader" id="registerLoader" style="display:none;"></span>
      </button>

      <div class="server-message error" id="registerError" style="display:none;"></div>
      <div class="server-message success" id="registerSuccess" style="display:none;"></div>

      <div class="register-link">
        <p>Already have an account? <a href="login.php">Login</a></p>
      </div>
    </form>
  </div>


  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- intl-tel-input v18 -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/18.1.1/js/intlTelInput.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/18.1.1/js/utils.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // --- Minimal city map keyed by ISO2
      const citiesByIso = {
        gh: ["Accra","Kumasi","Tamale","Takoradi"],
        ng: ["Lagos","Abuja","Kano","Ibadan"],
        gb: ["London","Manchester","Birmingham"],
        us: ["New York","Los Angeles","Chicago","Houston"]
      };

      // Initialize intl-tel-input
      const phoneInput = document.getElementById('phone_number');
      const iti = window.intlTelInput(phoneInput, {
        separateDialCode: true,
        initialCountry: "gh",
        preferredCountries: ['gh','ng','us','gb'],
        autoPlaceholder: 'aggressive',
        utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/18.1.1/js/utils.js"
      });

      const countrySelect = document.getElementById('country');
      const citySelect = document.getElementById('city');

      // populate country select
      const countryData = window.intlTelInputGlobals.getCountryData();
      countryData.sort((a,b) => a.name.localeCompare(b.name));
      countryData.forEach(cd => {
        const opt = document.createElement('option');
        opt.value = cd.iso2;
        opt.textContent = cd.name;
        countrySelect.appendChild(opt);
      });

      function updateHiddenPhoneFields() {
        try {
          const val = phoneInput.value.trim();
          let e164 = '';
          try { e164 = iti.getNumber(); } catch(e) { e164 = ''; }
          const country = iti.getSelectedCountryData();

          if (e164) document.getElementById('fullPhone').value = e164;
          if (country && country.dialCode) document.getElementById('countryCodeInput').value = '+' + country.dialCode;

          if (e164 && country && country.dialCode) {
            let digitsOnly = e164.replace(/\D+/g, '');
            const dial = country.dialCode;
            if (digitsOnly.startsWith(dial)) digitsOnly = digitsOnly.substring(dial.length);
            digitsOnly = digitsOnly.replace(/^0+/, '');
            if (!digitsOnly) digitsOnly = '0';
            document.getElementById('phoneLocalInput').value = digitsOnly;
          }

        } catch (err) {
          console.error('updateHiddenPhoneFields error:', err);
        }
      }

      phoneInput.addEventListener('countrychange', function() {
        const data = iti.getSelectedCountryData();
        if (data && data.iso2) {
          countrySelect.value = data.iso2;
          populateCitiesForIso(data.iso2);
        }
        updateHiddenPhoneFields();
      });

      phoneInput.addEventListener('blur', updateHiddenPhoneFields);
      phoneInput.addEventListener('input', function () {
        if (window._phoneUpdateTimeout) clearTimeout(window._phoneUpdateTimeout);
        window._phoneUpdateTimeout = setTimeout(updateHiddenPhoneFields, 300);
      });

      countrySelect.addEventListener('change', function() {
        const iso2 = this.value;
        if (iso2) {
          try {
            iti.setCountry(iso2);
          } catch(e) {
            console.warn('setCountry failed for', iso2, e);
          }
          populateCitiesForIso(iso2);
          updateHiddenPhoneFields();
        } else {
          citySelect.innerHTML = '<option value="">Select city</option>';
        }
      });

      function populateCitiesForIso(iso2) {
        citySelect.innerHTML = '<option value="">Select city</option>';
        const cities = (citiesByIso[iso2] || []);
        cities.forEach(ct => {
          const opt = document.createElement('option');
          opt.value = ct;
          opt.textContent = ct;
          citySelect.appendChild(opt);
        });
      }

      document.getElementById("registerForm").addEventListener("submit", function(event) {
        document.querySelectorAll('.error-message').forEach(e => e.style.display = 'none');
        updateHiddenPhoneFields();

        const email = document.getElementById("email").value.trim();
        const password = document.getElementById("password").value;
        const confirmPassword = document.getElementById("confirmPassword").value;

        const emailPattern = /^[a-zA-Z0-9._%+-]+@ashesi\.edu\.gh$/i;
        if (!emailPattern.test(email)) {
          document.getElementById("emailError").style.display = 'block';
          event.preventDefault();
          return;
        }

        const passwordPattern = /^(?=.*[A-Z])(?=(.*\d){3,})(?=.*[@#$%^&+=!]).{8,}$/;
        if (!passwordPattern.test(password)) {
          document.getElementById("passwordError").style.display = 'block';
          event.preventDefault();
          return;
        }

        if (password !== confirmPassword) {
          document.getElementById("confirmPasswordError").style.display = 'block';
          event.preventDefault();
          return;
        }

        if (!iti.isValidNumber()) {
          document.getElementById("phoneError").style.display = 'block';
          event.preventDefault();
          return;
        }

        const finalFullPhone = document.getElementById('fullPhone').value;
        const finalCountryCode = document.getElementById('countryCodeInput').value;
        const finalPhoneLocal = document.getElementById('phoneLocalInput').value;

        if (!finalFullPhone || !finalCountryCode || !finalPhoneLocal) {
          document.getElementById("phoneError").innerHTML = 'Phone processing error. Please try again.';
          document.getElementById("phoneError").style.display = 'block';
          event.preventDefault();
          return;
        }
      });

      setTimeout(() => {
        const initial = iti.getSelectedCountryData();
        if (initial && initial.iso2) {
          countrySelect.value = initial.iso2;
          populateCitiesForIso(initial.iso2);
        }
        updateHiddenPhoneFields();
      }, 200);
    });
  </script>
</body>
</html>
