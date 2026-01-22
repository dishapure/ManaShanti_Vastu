<h1>Astrology Appointment & User Dashboard System</h1>

<p>
This is a full-stack web application that allows users to sign up, log in, book astrology appointments,
track approval status, view past appointments, and see activity analytics through a personalized dashboard.
</p>

<hr>

<h2>Features</h2>
<ul>
  <li><b>User authentication</b> (signup, login, logout)</li>
  <li><b>Appointment booking system</b></li>
  <li><b>Astrologer approval workflow</b></li>
  <li><b>User dashboard</b> with upcoming and past appointments</li>
  <li><b>Appointment status tracking</b> (approved / pending)</li>
  <li><b>User profile</b> with avatar</li>
  <li><b>Analytics graphs</b> for visits and purchases</li>
</ul>

<hr>

<h2>Requirements</h2>
<ul>
  <li>XAMPP / WAMP / MAMP</li>
  <li>PHP 8+</li>
  <li>MySQL</li>
  <li>Web Browser (Chrome recommended)</li>
</ul>

<hr>

<h2>Setup Instructions</h2>

<h3>1. Install XAMPP</h3>
<p>
Download from:
<a href="https://www.apachefriends.org" target="_blank">https://www.apachefriends.org</a>
</p>
<p>
Start <b>Apache</b> and <b>MySQL</b> from XAMPP Control Panel.
</p>

<h3>2. Clone or Download the Project</h3>
<p>
Place the project folder inside:
</p>
<pre>
C:\xampp\htdocs\
</pre>

<h3>3. Create Database</h3>
<p>
Open:
</p>
<pre>
http://localhost/phpmyadmin
</pre>
<p>
Create a database (example):
</p>
<pre>
vastu_users
</pre>

<h3>4. Configure Database Connection</h3>
<p>
Update database credentials in PHP files:
</p>
<pre>
Host: localhost
Username: root
Password: (empty)
Database: vastu_users
</pre>

<h3>5. Run the Project</h3>
<p>
Open browser and visit:
</p>
<pre>
http://localhost/vastu_website/
</pre>

<hr>

<h2>Folder Structure</h2>
<pre>
/assets        - CSS, JS, images
/uploads       - User avatars
/login.php     - Login logic
/signup.php    - User registration
/dashboard.php - User dashboard
/logout.php    - Logout logic
</pre>

<hr>

<h2>Security</h2>
<ul>
  <li>Passwords are hashed using <b>PASSWORD_DEFAULT</b></li>
  <li>Sessions are used for authentication</li>
  <li>Logout destroys session securely</li>
</ul>

<hr>

<h2>Future Enhancements</h2>
<ul>
  <li>Astrologer / Admin dashboard</li>
  <li>Payment gateway integration</li>
  <li>Email & SMS notifications</li>
  <li>Advanced analytics</li>
</ul>

<hr>

<h2>Author</h2>
<p>
Built as a full-stack learning and practical implementation project using PHP and MySQL.
</p>
