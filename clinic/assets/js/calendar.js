
const monthYear = document.getElementById('monthYear');
const calendarDays = document.getElementById('calendarDays');
const selectedDateDisplay = document.getElementById('selectedDate');
const appointmentList = document.getElementById('appointmentList');

let date = new Date();
let selectedDate = null;
let appointments = {}; // Stores appointments grouped by date (YYYY-MM-DD)
let rawAppointments = []; // Stores the raw list of appointments from the PHP script

// Function to fetch and process data from PHP
async function fetchAppointments() {
    // IMPORTANT: Replace 'get_appointments.php' with the correct path to your PHP file.
    const url = 'fetch_all_appointments.php';

    try {
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const fetchedData = await response.json();

        if (fetchedData.error) {
            console.error("Server Error:", fetchedData.error);
            return;
        }

        // Store the raw list for easy lookup in displayAppointments
        rawAppointments = fetchedData;

        // Group appointments by date
        const newAppointments = {};
        rawAppointments.forEach(appt => {
            const dateKey = appt.dateKey; // YYYY-MM-DD from PHP

            if (!newAppointments[dateKey]) {
                newAppointments[dateKey] = [];
            }
            newAppointments[dateKey].push(appt);
        });

        appointments = newAppointments;

        console.log("Appointments loaded from DB:", appointments);
        renderCalendar(); // Re-render to display the fetched appointments

    } catch (error) {
        console.error("Error fetching appointments:", error);
    }
}

function renderCalendar() {
    const year = date.getFullYear();
    const month = date.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const today = new Date();

    monthYear.textContent = date.toLocaleString('default', { month: 'long', year: 'numeric' });
    calendarDays.innerHTML = '';

    // Day Names Row
    const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    dayNames.forEach(name => {
        const dayNameDiv = document.createElement('div');
        dayNameDiv.textContent = name;
        dayNameDiv.style.fontWeight = 'bold';
        dayNameDiv.style.padding = '5px 15px';
        dayNameDiv.style.textAlign = 'center';
        calendarDays.appendChild(dayNameDiv);
    });

    // Empty cells for alignment
    for (let i = 0; i < firstDay.getDay(); i++) {
        const empty = document.createElement('div');
        empty.classList.add('day'); // To match height/style
        empty.style.minHeight = '100px';
        empty.style.background = '#f2f4f8';
        empty.style.cursor = 'default';
        empty.style.boxShadow = 'none';
        calendarDays.appendChild(empty);
    }

    // Day Cells
    for (let day = 1; day <= lastDay.getDate(); day++) {
        const dayDiv = document.createElement('div');
        dayDiv.classList.add('day');

        // Format date key to match PHP output: YYYY-MM-DD
        const monthString = (month + 1).toString().padStart(2, '0');
        const dayString = day.toString().padStart(2, '0');
        const fullDate = `${year}-${monthString}-${dayString}`;

        // Highlight today
        if (
            day === today.getDate() &&
            month === today.getMonth() &&
            year === today.getFullYear()
        ) {
            dayDiv.classList.add('today');
        }

        // Highlight selected date
        if (fullDate === selectedDate) {
            dayDiv.classList.add('selected');
        }

        const number = document.createElement('div');
        number.classList.add('day-number');
        number.textContent = day;
        dayDiv.appendChild(number);

        // Mini appointment previews using fetched data
        const dayAppointments = appointments[fullDate] || [];
        dayAppointments.slice(0, 3).forEach(appt => {
            const mini = document.createElement('span');
            mini.classList.add('mini-appt');
            // Set the color based on the appointment status color from PHP
            mini.style.color = appt.color;
            mini.textContent = "• " + appt.title;
            dayDiv.appendChild(mini);
        });

        if (dayAppointments.length > 3) {
            const more = document.createElement('span');
            more.classList.add('mini-appt');
            more.textContent = `+${dayAppointments.length - 3} more`;
            dayDiv.appendChild(more);
        }

        dayDiv.addEventListener('click', () => selectDate(fullDate));
        calendarDays.appendChild(dayDiv);
    }
    // Initial display for the current date if selectedDate is null
    if (!selectedDate) {
        selectDate(`${today.getFullYear()}-${(today.getMonth() + 1).toString().padStart(2, '0')}-${today.getDate().toString().padStart(2, '0')}`);
    }
}

function selectDate(dateKey) {
    // Clear previous selection highlight
    document.querySelectorAll('.day.selected').forEach(el => el.classList.remove('selected'));
    selectedDate = dateKey;

    // Find and highlight the new selected day in the current view
    const [_, month, day] = dateKey.split('-');
    const currentViewMonth = date.getMonth() + 1;
    if (parseInt(month) === currentViewMonth) {
        // Find the dayDiv that contains the day number
        const dayDivs = calendarDays.querySelectorAll('.day');
        for (const div of dayDivs) {
            const dayNumberEl = div.querySelector('.day-number');
            if (dayNumberEl && parseInt(dayNumberEl.textContent) === parseInt(day)) {
                div.classList.add('selected');
                break;
            }
        }
    }

    displayAppointments();
}

function displayAppointments() {
    const displayDate = new Date(selectedDate);
    selectedDateDisplay.textContent = `Appointments on ${displayDate.toDateString()}`;
    const dayAppointments = appointments[selectedDate] || [];
    appointmentList.innerHTML = '';

    if (dayAppointments.length === 0) {
        appointmentList.innerHTML = '<p class="no-appointments">No appointments yet.</p>';
    } else {
        dayAppointments.forEach(appt => {
            const div = document.createElement('div');
            div.className = 'appointment-card';

            // Set border color based on status from PHP
            div.style.borderLeftColor = appt.color;

            // Card Header (Pet Name - Service)
            const title = document.createElement('h4');
            title.textContent = appt.title;
            div.appendChild(title);

            // Time and Status
            const timeStatus = document.createElement('div');
            timeStatus.className = 'time-status';

            const timeSpan = document.createElement('span');
            timeSpan.textContent = appt.extendedProps.time;

            timeStatus.appendChild(timeSpan);

            const statusSpan = document.createElement('span');
            statusSpan.className = 'status-badge';
            statusSpan.textContent = appt.extendedProps.status;
            statusSpan.style.backgroundColor = appt.color; // Use the same color for the badge
            timeStatus.appendChild(statusSpan);

            div.appendChild(timeStatus);

            // Details
            const clinic = document.createElement('p');
            clinic.innerHTML = `<strong>Clinic:</strong> ${appt.extendedProps.clinic}`;
            div.appendChild(clinic);



            const doctor = document.createElement('p');
            doctor.innerHTML = `<strong>Doctor:</strong> ${appt.extendedProps.doctor} (${appt.extendedProps.specialization})`;
            div.appendChild(doctor);

            console.log("here" + appt.extendedProps.time);

            appointmentList.appendChild(div);
        });
    }
}

document.getElementById('prev').onclick = () => {
    date.setMonth(date.getMonth() - 1);
    // When changing months, keep the selected date if it exists in the new month
    // Otherwise, default the selection to the 1st of the month.
    const newDay = Math.min(parseInt(selectedDate.split('-')[2]), new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate());
    const monthString = (date.getMonth() + 1).toString().padStart(2, '0');
    const dayString = newDay.toString().padStart(2, '0');
    selectedDate = `${date.getFullYear()}-${monthString}-${dayString}`;

    renderCalendar();
    displayAppointments();
};

document.getElementById('next').onclick = () => {
    date.setMonth(date.getMonth() + 1);
    // When changing months, keep the selected date if it exists in the new month
    const newDay = Math.min(parseInt(selectedDate.split('-')[2]), new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate());
    const monthString = (date.getMonth() + 1).toString().padStart(2, '0');
    const dayString = newDay.toString().padStart(2, '0');
    selectedDate = `${date.getFullYear()}-${monthString}-${dayString}`;

    renderCalendar();
    displayAppointments();
};

// Start the process: fetch data, then render the calendar.
fetchAppointments(); 