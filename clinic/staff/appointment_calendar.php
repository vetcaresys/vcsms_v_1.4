<?php
session_start();
include '../../config.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

// 🔒 Only for logged-in staff
if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'staff') {
  header('Location: ../clinic/staff/login.php');
  exit;
}

$staff_id = $_SESSION['staff_id'];
$clinic_id = $_SESSION['clinic_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Appointment Calendar</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">

<div class="container py-4">
  <h2 class="mb-4 text-primary">📅 Appointment Calendar</h2>
  <div id="calendar"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const calendarEl = document.getElementById('calendar');
  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    selectable: true,
    themeSystem: 'bootstrap5',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek'
    },
    events: {
      url: 'fetch_calendar_events.php',
      method: 'POST'
    },
    dateClick: function(info) {
      Swal.fire({
        title: `Add Note for ${info.dateStr}`,
        input: 'textarea',
        inputLabel: 'Write your note:',
        showCancelButton: true,
        confirmButtonText: 'Save Note',
        cancelButtonText: 'Cancel',
        preConfirm: (noteText) => {
          if (!noteText) return Swal.showValidationMessage('Please enter a note!');
          return fetch('save_calendar_note.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({ date: info.dateStr, note: noteText })
          })
          .then(response => response.json())
          .then(data => {
            if (!data.success) throw new Error(data.message);
            return data;
          })
          .catch(err => Swal.showValidationMessage(`Error: ${err.message}`));
        }
      }).then((result) => {
        if (result.isConfirmed) {
          Swal.fire('Saved!', 'Your note has been saved.', 'success');
        }
      });
    },
    eventClick: function(info) {
      Swal.fire({
        title: info.event.title,
        text: `Date: ${info.event.startStr}`,
        icon: 'info'
      });
    }
  });

  calendar.render();
});
</script>
</body>
</html>
