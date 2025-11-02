<!DOCTYPE html>
<html lang="ar" dir="rtl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>The Doctor - حجز موعد</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Fonts: Cairo -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet" />

    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <style>
      body { font-family: "Cairo", sans-serif; background-color: #f8fafc; }
      .step-indicator { transition: all 0.3s ease-in-out; }
      .step-active { background-color: #2563eb; color: white; font-weight: bold; }
      .card-hover-effect { transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out; }
      .card-hover-effect:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1); }
      .calendar-day { transition: all 0.2s ease; position: relative; }
      .calendar-day.available:hover { background-color: #dbeafe; cursor: pointer; }
      .calendar-day.selected { background-color: #2563eb; color: white; font-weight: bold; }
      .calendar-day .slots { position: absolute; bottom: 2px; right: 2px; font-size: 0.65rem; background-color: #10b981; color: white; padding: 1px 4px; border-radius: 99px; }
      .calendar-day.selected .slots { background-color: white; color: #2563eb; }
      .fade-in { animation: fadeIn 0.5s ease-in-out; }
      @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
      .spinner { border: 4px solid rgba(0, 0, 0, 0.1); width: 36px; height: 36px; border-radius: 50%; border-left-color: #2563eb; animation: spin 1s ease infinite; }
      @keyframes spin { to { transform: rotate(360deg); } }
    </style>
  </head>
  <body class="antialiased text-slate-800">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-40">
      <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
        <div class="text-2xl font-bold text-blue-600">
          <i class="fas fa-stethoscope mr-2"></i>The Doctor
        </div>
        <div>
          <a href="#specialty-selection" class="bg-blue-600 text-white font-bold py-2 px-5 rounded-full hover:bg-blue-700 transition-colors">احجز الآن</a>
          <button id="myAppointmentsBtn" class="bg-slate-200 text-slate-700 font-bold py-2 px-5 rounded-full hover:bg-slate-300 transition-colors mr-2">مواعيدي</button>
        </div>
      </nav>
    </header>

    <main class="container mx-auto px-6 py-8 md:py-12">
      <!-- Step Indicator -->
      <div class="w-full max-w-4xl mx-auto mb-12">
        <div class="flex items-center justify-center text-sm md:text-base">
          <div id="step-1-indicator" class="step-indicator step-active w-1/4 text-center py-2 px-1 rounded-r-full">1. اختر التخصص</div>
          <div id="step-2-indicator" class="step-indicator bg-slate-200 w-1/4 text-center py-2 px-1">2. اختر الطبيب</div>
          <div id="step-3-indicator" class="step-indicator bg-slate-200 w-1/4 text-center py-2 px-1">3. حدد الموعد</div>
          <div id="step-4-indicator" class="step-indicator bg-slate-200 w-1/4 text-center py-2 px-1 rounded-l-full">4. تأكيد الحجز</div>
        </div>
      </div>

      <div id="booking-flow-container">
        <!-- Phase 1: Specialty Selection -->
        <section id="specialty-selection" class="fade-in text-center">
          <h1 class="text-3xl md:text-4xl font-bold mb-4">أهلاً بك في صفحة الحجز</h1>
          <p class="text-slate-600 mb-8 max-w-2xl mx-auto">ابدأ رحلتك نحو صحة أفضل. اختر التخصص الطبي الذي تحتاجه من القائمة أدناه.</p>
          <div class="mb-10 max-w-lg mx-auto">
            <div class="relative">
              <span class="absolute inset-y-0 right-0 flex items-center pr-3"><i class="fas fa-search text-slate-400"></i></span>
              <input type="text" id="specialty-search" placeholder="ابحث عن تخصص..." class="w-full pr-10 pl-4 py-3 border border-slate-300 rounded-full focus:ring-blue-500 focus:border-blue-500" />
            </div>
          </div>
          <div id="specialty-list" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <div class="col-span-full text-center"><div class="spinner mx-auto"></div><p class="mt-2 text-slate-500">جاري تحميل التخصصات...</p></div>
          </div>
        </section>
        
        <!-- Phase 1.2: Doctor Selection -->
        <section id="doctor-selection" class="hidden fade-in">
          <div class="flex items-center mb-6">
            <button class="back-btn text-slate-500 hover:text-blue-600" data-target="specialty-selection"><i class="fas fa-arrow-right ml-2"></i> رجوع</button>
            <h2 id="doctor-list-title" class="text-2xl md:text-3xl font-bold mr-4">الأطباء المتاحون في تخصص:</h2>
          </div>
          <div class="flex flex-col md:flex-row gap-4 mb-8 bg-white p-4 rounded-xl shadow-sm">
            <div class="relative flex-grow">
              <span class="absolute inset-y-0 right-0 flex items-center pr-3"><i class="fas fa-search text-slate-400"></i></span>
              <input type="text" id="doctor-name-search" placeholder="ابحث عن طبيب بالاسم..." class="w-full pr-10 pl-4 py-2 border border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"/>
            </div>
            <div class="relative flex-grow">
              <span class="absolute inset-y-0 right-0 flex items-center pr-3"><i class="fas fa-map-marker-alt text-slate-400"></i></span>
              <select id="wilaya-filter" class="w-full appearance-none pr-10 pl-4 py-2 border border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-white">
                 <option value="">كل الولايات</option>
              </select>
            </div>
          </div>
          <div id="doctor-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="col-span-full text-center"><div class="spinner mx-auto"></div></div>
          </div>
        </section>

        <!-- Phase 2: Booking Calendar -->
        <section id="booking-calendar" class="hidden fade-in">
          <div class="flex items-center mb-8">
            <button class="back-btn text-slate-500 hover:text-blue-600" data-target="doctor-selection"><i class="fas fa-arrow-right ml-2"></i> رجوع</button>
            <h2 id="calendar-title" class="text-2xl md:text-3xl font-bold mr-4">اختر اليوم المناسب للحجز مع:</h2>
          </div>
          <div class="bg-white p-6 rounded-2xl shadow-lg max-w-4xl mx-auto">
            <div class="flex flex-col md:flex-row gap-6">
              <div class="w-full md:w-2/3">
                <div class="flex justify-between items-center mb-4">
                  <button id="prev-month" class="text-slate-500 hover:text-blue-600 p-2 rounded-full"><i class="fas fa-chevron-right"></i></button>
                  <h3 id="month-year" class="text-xl font-bold"></h3>
                  <button id="next-month" class="text-slate-500 hover:text-blue-600 p-2 rounded-full"><i class="fas fa-chevron-left"></i></button>
                </div>
                <div id="calendar-grid-container" class="relative">
                    <div id="calendar-grid" class="grid grid-cols-7 gap-2 text-center"></div>
                    <div id="calendar-loader" class="hidden absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center"><div class="spinner"></div></div>
                </div>
              </div>
              <div class="w-full md:w-1/3 border-r-0 md:border-r-2 border-slate-100 pr-0 md:pr-6">
                <h3 class="font-bold text-lg mb-4">تفاصيل اليوم المختار</h3>
                <div id="day-details" class="text-center bg-slate-50 p-4 rounded-lg"><p class="text-slate-500">الرجاء اختيار يوم من التقويم</p></div>
                <button id="confirm-day-btn" class="w-full bg-blue-600 text-white font-bold py-3 mt-4 rounded-lg hover:bg-blue-700 transition-colors disabled:bg-slate-300 disabled:cursor-not-allowed" disabled>اختر هذا اليوم</button>
              </div>
            </div>
          </div>
        </section>

        <!-- Phase 4: Confirmation -->
        <section id="confirmation-page" class="hidden fade-in text-center">
            <div class="bg-white max-w-2xl mx-auto p-8 rounded-2xl shadow-lg">
                <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6"><i class="fas fa-check text-4xl"></i></div>
                <h2 class="text-3xl font-bold mb-3">تم تأكيد حجزك بنجاح!</h2>
                <p class="text-slate-600 mb-6">شكراً لك. ستصلك رسالة تذكير قبل موعدك بيوم.</p>
                <div class="text-right bg-slate-50 p-6 rounded-lg border border-slate-200 space-y-3">
                    <p><strong>رقم الحجز:</strong> <span id="conf-booking-id"></span></p>
                    <p><strong>الطبيب:</strong> <span id="conf-doctor-name"></span></p>
                    <p><strong>التخصص:</strong> <span id="conf-specialty"></span></p>
                    <p><strong>التاريخ:</strong> <span id="conf-date"></span></p>
                </div>
                <button id="book-another-btn" class="mt-8 bg-blue-600 text-white font-bold py-3 px-8 rounded-full hover:bg-blue-700 transition-colors">حجز موعد آخر</button>
            </div>
        </section>

      </div>
    </main>

    <!-- Modals -->
    <div id="auth-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-8 relative fade-in">
        <button id="close-modal-btn" class="absolute top-4 left-4 text-slate-500 hover:text-slate-800 text-2xl">×</button>
        <div id="auth-content">
          <h2 class="text-2xl font-bold text-center mb-2">أنت على بعد خطوة واحدة!</h2>
          <p class="text-slate-600 text-center mb-6">لتأكيد حجزك، يرجى تسجيل الدخول أو إنشاء حساب جديد.</p>
          <div id="auth-error" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 text-sm" role="alert"></div>
          <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 mb-6 text-sm">
            <p><strong>ملخص الحجز:</strong></p>
            <p>الطبيب: <span id="modal-doctor-name" class="font-semibold"></span></p>
            <p>التاريخ: <span id="modal-date" class="font-semibold"></span></p>
          </div>
          <div class="flex border-b mb-6">
            <button id="login-tab" class="py-2 px-4 text-blue-600 border-b-2 border-blue-600 font-semibold">تسجيل الدخول</button>
            <button id="register-tab" class="py-2 px-4 text-slate-500">إنشاء حساب</button>
          </div>
          <form id="login-form">
            <div class="mb-4">
              <label for="login-email" class="block text-sm font-medium text-slate-700 mb-1">البريد الإلكتروني</label>
              <input type="email" id="login-email" value="" placeholder="patient@email.com" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required />
            </div>
            <div class="mb-6">
              <label for="login-password" class="block text-sm font-medium text-slate-700 mb-1">كلمة المرور</label>
              <input type="password" id="login-password" value="" placeholder="********" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required/>
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition-colors">تأكيد الحجز</button>
          </form>
          <form id="register-form" class="hidden">
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="register-firstname" class="block text-sm font-medium text-slate-700 mb-1">الاسم الأول</label>
                    <input type="text" id="register-firstname" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required/>
                </div>
                <div>
                    <label for="register-lastname" class="block text-sm font-medium text-slate-700 mb-1">الاسم الأخير</label>
                    <input type="text" id="register-lastname" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required/>
                </div>
            </div>
            <div class="mb-4">
              <label for="register-email" class="block text-sm font-medium text-slate-700 mb-1">البريد الإلكتروني</label>
              <input type="email" id="register-email" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required/>
            </div>
            <div class="mb-6">
              <label for="register-password" class="block text-sm font-medium text-slate-700 mb-1">كلمة المرور</label>
              <input type="password" id="register-password" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required/>
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition-colors">إنشاء حساب وتأكيد الحجز</button>
          </form>
        </div>
      </div>
    </div>
    <div id="my-appointments-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl p-8 relative fade-in">
        <button id="close-appointments-modal-btn" class="absolute top-4 left-4 text-slate-500 hover:text-slate-800 text-2xl">×</button>
        <h2 class="text-2xl font-bold text-center mb-6">مواعيـدي</h2>
        <div id="appointments-list-container" class="space-y-4 max-h-[60vh] overflow-y-auto pr-2">
          <p class="text-center text-slate-500">جاري تحميل مواعيدك...</p>
        </div>
      </div>
    </div>

    <script>
      document.addEventListener("DOMContentLoaded", function () {
        // --- Configuration ---
        const API_BASE_URL = "https://admin.the-doctor.app/web-api";

        // --- DOM Elements ---
        const specialtySection = document.getElementById("specialty-selection");
        const doctorSection = document.getElementById("doctor-selection");
        const calendarSection = document.getElementById("booking-calendar");
        const confirmationSection = document.getElementById("confirmation-page");
        const authModal = document.getElementById("auth-modal");
        const myAppointmentsModal = document.getElementById("my-appointments-modal");
        const specialtySearchInput = document.getElementById("specialty-search");
        const doctorNameSearchInput = document.getElementById("doctor-name-search");
        const wilayaFilterSelect = document.getElementById("wilaya-filter");
        const calendarLoader = document.getElementById('calendar-loader');
        const authErrorDiv = document.getElementById('auth-error');

        // --- Data & State ---
        const sections = { "specialty-selection": specialtySection, "doctor-selection": doctorSection, "booking-calendar": calendarSection, "confirmation-page": confirmationSection };
        const stepIndicators = { 1: document.getElementById("step-1-indicator"), 2: document.getElementById("step-2-indicator"), 3: document.getElementById("step-3-indicator"), 4: document.getElementById("step-4-indicator") };
        
        let allSpecialties = [];
        let allWilayas = [];
        let allDoctorsForSpecialty = [];
        let currentState = { specialty: null, doctor: null, date: null };
        let calendarDate = new Date();
        let loggedInPatient = null;
        
        // --- Helper Functions ---
        async function apiCall(endpoint, payload) {
          try {
            const response = await fetch(`${API_BASE_URL}${endpoint}`, {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify(payload),
            });
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return await response.json();
          } catch (error) {
            console.error("API Call Error:", error);
            return null;
          }
        }
        
        function updateStepIndicator(step) {
          Object.values(stepIndicators).forEach(el => { el.classList.remove("step-active", "bg-blue-600", "text-white"); el.classList.add("bg-slate-200"); });
          for (let i = 1; i <= step; i++) { stepIndicators[i].classList.add("step-active"); stepIndicators[i].classList.remove("bg-slate-200"); }
        }

        function showSection(sectionId) {
          Object.values(sections).forEach((s) => s.classList.add("hidden"));
          if (sections[sectionId]) { sections[sectionId].classList.remove("hidden"); window.scrollTo({ top: 0, behavior: "smooth" }); }
          if (sectionId === "specialty-selection") updateStepIndicator(1);
          else if (sectionId === "doctor-selection") updateStepIndicator(2);
          else if (sectionId === "booking-calendar") updateStepIndicator(3);
          else if (sectionId === "confirmation-page") updateStepIndicator(4);
        }

        function showAuthError(message) {
            authErrorDiv.textContent = message;
            authErrorDiv.classList.remove('hidden');
        }

        // --- Rendering Functions ---
        function renderSpecialties(searchTerm = "") {
            const listEl = document.getElementById("specialty-list");
            if(allSpecialties.length === 0) {
                 listEl.innerHTML = `<p class="col-span-full text-center text-red-500">فشل في تحميل التخصصات.</p>`;
                 return;
            }
            const filteredSpecialties = allSpecialties.filter((s) => s.namefr.toLowerCase().includes(searchTerm.trim().toLowerCase()));
            if (filteredSpecialties.length === 0) {
                listEl.innerHTML = `<p class="col-span-full text-center text-slate-500">لا يوجد تخصص يطابق بحثك.</p>`;
                return;
            }
            const getIcon = (name) => {
                const lowerName = name.toLowerCase();
                if (lowerName.includes("dental") || lowerName.includes("أسنان")) return "fa-tooth";
                if (lowerName.includes("pediatrics") || lowerName.includes("أطفال")) return "fa-baby";
                if (lowerName.includes("cardiology") || lowerName.includes("قلب")) return "fa-heart-pulse";
                if (lowerName.includes("dermatology") || lowerName.includes("جلد")) return "fa-spa";
                if (lowerName.includes("neurology") || lowerName.includes("أعصاب")) return "fa-brain";
                if (lowerName.includes("orthopedics") || lowerName.includes("عظام")) return "fa-bone";
                if (lowerName.includes("ophthalmology") || lowerName.includes("عيون")) return "fa-eye";
                return "fa-user-doctor";
            };
            listEl.innerHTML = filteredSpecialties.map(s => `<div class="bg-white p-6 rounded-2xl shadow-md text-center cursor-pointer card-hover-effect" data-specialty-id="${s.id}"><i class="fas ${getIcon(s.namefr)} text-5xl text-blue-500 mb-4"></i><h3 class="font-bold text-lg">${s.namefr}</h3></div>`).join("");
        }
        
        function populateWilayaFilter() {
            wilayaFilterSelect.innerHTML = `<option value="">كل الولايات</option>`;
            allWilayas.forEach((w) => {
                wilayaFilterSelect.innerHTML += `<option value="${w.willaya}">${w.willaya}</option>`;
            });
        }

        function renderFilteredDoctors(nameFilter = "", wilayaFilter = "") {
            const listEl = document.getElementById("doctor-list");
            let filteredDoctors = allDoctorsForSpecialty;
            if (nameFilter) {
                const fullNameFilter = nameFilter.trim().toLowerCase();
                filteredDoctors = filteredDoctors.filter((d) => `${d.first_name} ${d.last_name}`.toLowerCase().includes(fullNameFilter));
            }
            if (wilayaFilter) {
                filteredDoctors = filteredDoctors.filter((d) => d.willaya === wilayaFilter);
            }
            if (filteredDoctors.length === 0) {
                listEl.innerHTML = `<p class="col-span-full text-center text-slate-500">لا يوجد أطباء يطابقون معايير البحث.</p>`;
                return;
            }
            listEl.innerHTML = filteredDoctors.map(d => `<div class="bg-white p-6 rounded-2xl shadow-md flex flex-col items-center text-center card-hover-effect"><img src="${ d.image1 ? d.image1 : 'https://placehold.co/200x200/E2E8F0/475569?text=' + d.first_name.charAt(0) }" alt="${d.first_name} ${d.last_name}" class="w-24 h-24 rounded-full mb-4 border-4 border-slate-100 object-cover"><h3 class="font-bold text-xl">${d.degree || ''} ${d.first_name} ${d.last_name}</h3><div class="text-sm text-slate-500 mb-1"><i class="fas fa-map-marker-alt mr-1 text-slate-400"></i>${ d.willaya || "غير محدد"} - ${ d.name || "غير محدد"}</div><div class="text-yellow-500 my-2"><i class="fas fa-star"></i> ${d.recomondation || "0"}</div><p class="text-slate-500 text-sm mb-4 h-24 overflow-hidden">${ d.description || "نبذة قصيرة عن خبرة الطبيب وإنجازاته في مجاله الطبي." }</p><button class="mt-auto w-full bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors" data-doctor-id="${d.id}">احجز الآن</button></div>`).join("");
        }

        async function fetchAndRenderDoctors(specialtyId) {
            document.getElementById("doctor-list").innerHTML = `<div class="col-span-full text-center"><div class="spinner mx-auto"></div></div>`;
            const response = await apiCall("/v1/doctors", { specialty_id: specialtyId });
            if (response && response.data) {
                allDoctorsForSpecialty = response.data;
                renderFilteredDoctors();
            } else {
                document.getElementById("doctor-list").innerHTML = `<p class="col-span-full text-center text-red-500">فشل في تحميل قائمة الأطباء.</p>`;
            }
        }

        async function fetchBookedCounts(doctorId, year, month) {
            const firstDay = `${year}-${String(month + 1).padStart(2, '0')}-01`;
            const lastDayDate = new Date(year, month + 1, 0);
            const lastDay = `${year}-${String(month + 1).padStart(2, '0')}-${String(lastDayDate.getDate()).padStart(2, '0')}`;
            const payload = { sql: `SELECT DATE(date) as rdv_date, COUNT(id) as booked_count FROM rdv WHERE doctor_id = ${doctorId} AND date BETWEEN '${firstDay}' AND '${lastDay}' AND state IN (0, 1) GROUP BY DATE(date)` };
            const response = await apiCall('/v1/endpoint', payload);
            const bookedMap = {};
            if (response && response.data) {
                response.data.forEach(item => { bookedMap[item.rdv_date] = item.booked_count; });
            }
            return bookedMap;
        }

        async function renderCalendar() {
            const doctor = currentState.doctor;
            document.getElementById("calendar-title").innerHTML = `اختر اليوم المناسب للحجز مع: <span class="text-blue-600">${doctor.degree || ''} ${doctor.first_name} ${doctor.last_name}</span>`;
            calendarLoader.classList.remove('hidden');
            const calendarGridEl = document.getElementById("calendar-grid");
            calendarGridEl.innerHTML = '';
            
            const year = calendarDate.getFullYear();
            const month = calendarDate.getMonth();
            const bookedCounts = await fetchBookedCounts(doctor.id, year, month);
            
            calendarLoader.classList.add('hidden');
            const monthYearEl = document.getElementById("month-year");
            monthYearEl.textContent = new Intl.DateTimeFormat("ar-EG", { month: "long", year: "numeric" }).format(calendarDate);

            const firstDayIndex = new Date(year, month, 1).getDay();
            const lastDay = new Date(year, month + 1, 0).getDate();
            const prevLastDay = new Date(year, month, 0).getDate();
            
            calendarGridEl.innerHTML = '<div class="font-semibold">ح</div><div class="font-semibold">ن</div><div class="font-semibold">ث</div><div class="font-semibold">ر</div><div class="font-semibold">خ</div><div class="font-semibold">ج</div><div class="font-semibold">س</div>';
            for (let x = firstDayIndex; x > 0; x--) {
                calendarGridEl.innerHTML += `<div class="py-2 text-slate-300">${prevLastDay - x + 1}</div>`;
            }

            const workHours = JSON.parse(doctor.travel_hours || '{}');
            const ticketsPerDay = JSON.parse(doctor.tickets_day || '{}');
            const dayNameToIndex = { "Dimanche": 0, "Lundi": 1, "Mardi": 2, "Mercredi": 3, "Jeudi": 4, "Vendredi": 5, "Samedi": 6 };

            for (let i = 1; i <= lastDay; i++) {
                const dayDate = new Date(year, month, i);
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                const dayNameFrench = Object.keys(dayNameToIndex).find(key => dayNameToIndex[key] === dayDate.getDay());
                const isWorkDay = workHours[dayNameFrench] && workHours[dayNameFrench].from && workHours[dayNameFrench].to;
                const dateString = `${year}-${String(month + 1).padStart(2, "0")}-${String(i).padStart(2, "0")}`;
                const totalSlots = ticketsPerDay[dayNameFrench] || 0;
                const bookedSlots = bookedCounts[dateString] || 0;
                const availableSlots = totalSlots - bookedSlots;

                if (dayDate < today || !isWorkDay || availableSlots <= 0) {
                    calendarGridEl.innerHTML += `<div class="py-2 text-slate-300" title="غير متاح">${i}</div>`;
                } else {
                    let dayClasses = "calendar-day py-2 rounded-lg available";
                    let selectedDateStr = currentState.date ? `${currentState.date.getFullYear()}-${String(currentState.date.getMonth() + 1).padStart(2, "0")}-${String(currentState.date.getDate()).padStart(2, "0")}` : null;
                    if (dateString === selectedDateStr) dayClasses += " selected";
                    calendarGridEl.innerHTML += `<div class="${dayClasses}" data-date="${dateString}" data-slots="${availableSlots}">${i}<span class="slots">${availableSlots}</span></div>`;
                }
            }
            
            const lastDayIndex = new Date(year, month, lastDay).getDay();
            const nextDays = 6 - lastDayIndex;
            for (let j = 1; j <= nextDays; j++) {
                calendarGridEl.innerHTML += `<div class="py-2 text-slate-300">${j}</div>`;
            }
        }

        function updateDayDetails(date) {
            const dayDetailsEl = document.getElementById("day-details");
            const confirmBtn = document.getElementById("confirm-day-btn");
            if (!date) {
                dayDetailsEl.innerHTML = `<p class="text-slate-500">الرجاء اختيار يوم من التقويم</p>`;
                confirmBtn.disabled = true;
                return;
            }
            const dayElement = document.querySelector(`.calendar-day[data-date="${date}"]`);
            if (!dayElement) return;
            const slots = dayElement.dataset.slots || 0;
            const formattedDate = new Intl.DateTimeFormat("ar-EG", { weekday: "long", year: "numeric", month: "long", day: "numeric" }).format(currentState.date);
            dayDetailsEl.innerHTML = `<p class="font-bold text-lg mb-2">${formattedDate}</p><p>المقاعد المتاحة: <span class="font-bold text-green-600">${slots}</span></p>`;
            confirmBtn.disabled = false;
        }
        
        // --- Authentication & Booking ---
        function showAuthModal() {
            authErrorDiv.classList.add('hidden');
            const doctor = currentState.doctor;
            document.getElementById("modal-doctor-name").textContent = `${doctor.degree || ''} ${doctor.first_name} ${doctor.last_name}`;
            document.getElementById("modal-date").textContent = new Intl.DateTimeFormat("ar-EG", { weekday: "long", year: "numeric", month: "long", day: "numeric" }).format(currentState.date);
            
            if(loggedInPatient) { // If user is already logged in, book directly
                handleBookingConfirmation();
            } else {
                authModal.classList.remove("hidden");
            }
        }
        
      

        async function handlePatientAuthAndBooking(formId, isRegister = false) {
            const form = document.getElementById(formId);
            const submitButton = form.querySelector("button[type=submit]");
            const emailInput = form.querySelector(isRegister ? '#register-email' : '#login-email');
            const passwordInput = form.querySelector(isRegister ? '#register-password' : '#login-password');
            authErrorDiv.classList.add('hidden');

            // --- START: New Validation ---
            if (isRegister) {
                const firstName = form.querySelector('#register-firstname').value.trim();
                const lastName = form.querySelector('#register-lastname').value.trim();
                const email = emailInput.value.trim();
                const password = passwordInput.value.trim();
                if (!firstName || !lastName || !email || !password) {
                    showAuthError('الرجاء تعبئة جميع الحقول لإنشاء الحساب.');
                    return;
                }
            }
            // --- END: New Validation ---

            submitButton.disabled = true;
            submitButton.innerHTML = '<div class="spinner mx-auto" style="height:20px; width:20px; border-width: 2px;"></div>';
            
            let patient;

            if (isRegister) {
                const firstName = form.querySelector('#register-firstname').value;
                const lastName = form.querySelector('#register-lastname').value;
                const registerPayload = { table: "patient", data: { first_name: firstName, last_name: lastName, email: emailInput.value, password: passwordInput.value }};
                const registerResponse = await apiCall('/v1/endpoint', registerPayload);
                
                // Improved error check
                if (!registerResponse || !registerResponse.id || (registerResponse.id && registerResponse.id.errorInfo)) {
                    const dbError = registerResponse.id && registerResponse.id.errorInfo ? registerResponse.id.errorInfo[2] : '';
                    if (dbError.includes("Duplicate entry")) {
                        showAuthError('هذا البريد الإلكتروني مستخدم بالفعل.');
                    } else {
                        showAuthError('فشل في إنشاء الحساب. الرجاء المحاولة مرة أخرى.');
                    }
                    submitButton.disabled = false; submitButton.textContent = "إنشاء حساب وتأكيد الحجز";
                    return;
                }
                patient = { id: registerResponse.id, first_name: firstName, last_name: lastName, email: emailInput.value };
            } else {
                const escapedEmail = emailInput.value.replace(/'/g, "''");
                const escapedPassword = passwordInput.value.replace(/'/g, "''");
                const loginPayload = { table: "patient", exact: `email = '${escapedEmail}' AND password = '${escapedPassword}'`};
                const loginResponse = await apiCall('/v1/endpoint', loginPayload);
                if (!loginResponse || !loginResponse.data || loginResponse.data.length === 0) {
                    showAuthError('البريد الإلكتروني أو كلمة المرور غير صحيحة.');
                    submitButton.disabled = false; submitButton.textContent = "تأكيد الحجز";
                    return;
                }
                patient = loginResponse.data[0];
            }

            if (patient && patient.id) {
                loggedInPatient = patient;
                localStorage.setItem('theDoctorPatient', JSON.stringify(patient));
                await handleBookingConfirmation();
                authModal.classList.add("hidden");
            }
            submitButton.disabled = false;
            submitButton.textContent = isRegister ? "إنشاء حساب وتأكيد الحجز" : "تأكيد الحجز";
        }
        async function handleBookingConfirmation() {
            if (!loggedInPatient) { alert("حدث خطأ. الرجاء تسجيل الدخول مرة أخرى."); return; }
            const dateString = `${currentState.date.getFullYear()}-${String(currentState.date.getMonth() + 1).padStart(2,"0")}-${String(currentState.date.getDate()).padStart(2,"0")}`;
            const payload = { table: "rdv", data: { patient_id: loggedInPatient.id, doctor_id: currentState.doctor.id, date: dateString, state: 0 }};
            const response = await apiCall("/v1/endpoint", payload);
            if (response && response.id) {
                showConfirmationPage(response.id);
            } else {
                alert("حدث خطأ أثناء تأكيد الحجز. قد يكون الطبيب غير متاح في هذا اليوم.");
            }
        }

        function showConfirmationPage(bookingId) {
            const doctor = currentState.doctor;
            const specialty = allSpecialties.find(s => s.id == doctor.specialty_id);
            document.getElementById("conf-booking-id").textContent = `RDV-${bookingId}`;
            document.getElementById("conf-doctor-name").textContent = `${doctor.degree || ''} ${doctor.first_name} ${doctor.last_name}`;
            document.getElementById("conf-specialty").textContent = specialty.namefr;
            document.getElementById("conf-date").textContent = new Intl.DateTimeFormat("ar-EG", { weekday: "long", year: "numeric", month: "long", day: "numeric" }).format(currentState.date);
            showSection("confirmation-page");
        }

        async function renderMyAppointments() {
            const container = document.getElementById("appointments-list-container");
            container.innerHTML = `<div class="text-center"><div class="spinner mx-auto"></div></div>`;
            if (!loggedInPatient) {
                container.innerHTML = `<p class="text-center text-slate-500">الرجاء تسجيل الدخول أولاً لعرض مواعيدك.</p>`;
                return;
            }
            const response = await apiCall("/v1/rdv", { idUser: loggedInPatient.id });
            if (!response || !response.data) { container.innerHTML = `<p class="text-center text-red-500">فشل في تحميل المواعيد.</p>`; return; }
            if (response.data.length === 0) { container.innerHTML = `<p class="text-center text-slate-500">لا توجد لديك أي مواعيد محجوزة حالياً.</p>`; return; }
            const statusMap = { 0: { text: "قيد الانتظار", color: "yellow" }, 1: { text: "مؤكد", color: "green" }, 2: { text: "مكتمل", color: "blue" }, 3: { text: "ملغى", color: "red" } };
            container.innerHTML = response.data.map(apt => {
                const status = statusMap[apt.state] || { text: "غير معروف", color: "gray" };
                const aptDate = new Intl.DateTimeFormat("ar-EG", { dateStyle: "full" }).format(new Date(apt.date));
                return `<div class="bg-slate-50 border border-slate-200 rounded-lg p-4 flex justify-between items-center"><div><p class="font-bold">${apt.specialty} - ${apt.first_name} ${apt.last_name}</p><p class="text-sm text-slate-600">${aptDate}</p></div><div><span class="text-sm font-bold text-${status.color}-600 bg-${status.color}-100 py-1 px-3 rounded-full">${status.text}</span></div></div>`;
            }).join("");
        }

        // --- Event Listeners ---
        specialtySearchInput.addEventListener("input", (e) => renderSpecialties(e.target.value));
        doctorNameSearchInput.addEventListener("input", () => renderFilteredDoctors(doctorNameSearchInput.value, wilayaFilterSelect.value));
        wilayaFilterSelect.addEventListener("change", () => renderFilteredDoctors(doctorNameSearchInput.value, wilayaFilterSelect.value));
        document.getElementById("specialty-list").addEventListener("click", function (e) {
            const card = e.target.closest("[data-specialty-id]");
            if (card) {
                currentState.specialty = allSpecialties.find(s => s.id == card.dataset.specialtyId);
                doctorNameSearchInput.value = "";
                fetchAndRenderDoctors(currentState.specialty.id);
                showSection("doctor-selection");
                document.getElementById("doctor-list-title").innerHTML = `الأطباء المتاحون في تخصص: <span class="text-blue-600">${currentState.specialty.namefr}</span>`;
            }
        });
        document.getElementById("doctor-list").addEventListener("click", function (e) {
            const btn = e.target.closest("[data-doctor-id]");
            if (btn) {
              currentState.doctor = allDoctorsForSpecialty.find((d) => d.id == btn.dataset.doctorId);
              currentState.date = null; calendarDate = new Date();
              renderCalendar(); updateDayDetails(null);
              showSection("booking-calendar");
            }
        });
        document.querySelectorAll(".back-btn").forEach(btn => btn.addEventListener("click", function () {
            const targetSection = this.dataset.target;
            if (targetSection === "specialty-selection") { currentState.specialty = null; currentState.doctor = null; }
            if (targetSection === "doctor-selection") { currentState.doctor = null; }
            showSection(targetSection);
        }));
        document.getElementById("calendar-grid").addEventListener("click", function (e) {
            const dayEl = e.target.closest(".calendar-day.available");
            if (dayEl) {
              const dateStr = dayEl.dataset.date;
              currentState.date = new Date(dateStr + "T00:00:00");
              document.querySelectorAll(".calendar-day.selected").forEach((el) => el.classList.remove("selected"));
              dayEl.classList.add("selected");
              updateDayDetails(dateStr);
            }
        });
        document.getElementById("prev-month").addEventListener("click", () => { calendarDate.setMonth(calendarDate.getMonth() - 1); renderCalendar(); });
        document.getElementById("next-month").addEventListener("click", () => { calendarDate.setMonth(calendarDate.getMonth() + 1); renderCalendar(); });
        document.getElementById("confirm-day-btn").addEventListener("click", () => { updateStepIndicator(4); showAuthModal(); });
        document.getElementById("close-modal-btn").addEventListener("click", () => { authModal.classList.add("hidden"); updateStepIndicator(3); });
        
        const loginTab = document.getElementById("login-tab"), registerTab = document.getElementById("register-tab"), loginForm = document.getElementById("login-form"), registerForm = document.getElementById("register-form");
        loginTab.addEventListener("click", () => { loginTab.classList.add("text-blue-600", "border-b-2"); loginTab.classList.remove("text-slate-500"); registerTab.classList.remove("text-blue-600", "border-b-2"); registerTab.classList.add("text-slate-500"); loginForm.classList.remove("hidden"); registerForm.classList.add("hidden"); authErrorDiv.classList.add('hidden');});
        registerTab.addEventListener("click", () => { registerTab.classList.add("text-blue-600", "border-b-2"); registerTab.classList.remove("text-slate-500"); loginTab.classList.remove("text-blue-600", "border-b-2"); loginTab.classList.add("text-slate-500"); registerForm.classList.remove("hidden"); loginForm.classList.add("hidden"); authErrorDiv.classList.add('hidden');});

        loginForm.addEventListener("submit", (e) => { e.preventDefault(); handlePatientAuthAndBooking('login-form', false); });
        registerForm.addEventListener("submit", (e) => { e.preventDefault(); handlePatientAuthAndBooking('register-form', true); });
        
        document.getElementById("book-another-btn").addEventListener("click", () => { currentState = { specialty: null, doctor: null, date: null }; allDoctorsForSpecialty = []; showSection("specialty-selection"); });
        document.getElementById("myAppointmentsBtn").addEventListener("click", () => { myAppointmentsModal.classList.remove("hidden"); renderMyAppointments(); });
        document.getElementById("close-appointments-modal-btn").addEventListener("click", () => { myAppointmentsModal.classList.add("hidden"); });
        
        // --- Initial Application Load ---
        async function initializeApp() {
            const storedPatient = localStorage.getItem('theDoctorPatient');
            if (storedPatient) {
                loggedInPatient = JSON.parse(storedPatient);
            }

            const specialtiesPromise = apiCall('/v1/endpoint', { sql: "SELECT id, namefr FROM specialty WHERE deleted = 0 ORDER BY namefr ASC" });
            const wilayasPromise = apiCall('/v1/endpoint', { sql: "SELECT DISTINCT willaya FROM doctor WHERE willaya IS NOT NULL AND deleted = 0 ORDER BY willaya ASC" });

            const [specialtiesResponse, wilayasResponse] = await Promise.all([specialtiesPromise, wilayasPromise]);

            if (specialtiesResponse && specialtiesResponse.data) {
                allSpecialties = specialtiesResponse.data;
            }
            if (wilayasResponse && wilayasResponse.data) {
                allWilayas = wilayasResponse.data;
            }
            
            renderSpecialties();
            populateWilayaFilter();
            showSection("specialty-selection");
        }
        initializeApp();
      });
    </script>
  </body>
</html>