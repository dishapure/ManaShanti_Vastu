document.getElementById("bookingForm").addEventListener("submit", function (e) {
  e.preventDefault();

  const name = document.getElementById("name").value.trim();
  const dob = document.getElementById("dob").value.trim();
  const birthTime = document.getElementById("birthTime").value.trim();
  const birthPlace = document.getElementById("birthPlace").value.trim();
  const referrer = document.getElementById("referrer").value.trim();
  const profession = document.getElementById("profession").value.trim();
  const location = document.getElementById("location").value.trim();
  const phone = document.getElementById("phone").value.trim();

  // New: appointment type
  const appointmentType = document.querySelector('input[name="appointmentType"]:checked');

  if (!name || !dob || !birthTime || !birthPlace || !phone) {
    alert("❗Please fill in all required fields.");
    return;
  }

  if (!appointmentType) {
    alert("❗ Please select appointment type (Online or Offline).");
    return;
  }

  if (!/^\d{10}$/.test(phone)) {
    alert("📞 Please enter a valid 10-digit phone number.");
    return;
  }

  const bookingSuccess = document.getElementById("bookingSuccess");
  bookingSuccess.textContent = "🪐 Thank you! Your appointment request has been received. We will contact you shortly.";
  bookingSuccess.style.display = "block";

  this.reset();

  setTimeout(() => {
    bookingSuccess.style.display = "none";
  }, 5000);
});
