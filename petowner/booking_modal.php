<!-- Booking Modal -->
<div class="modal fade" id="bookingModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-calendar-check"></i> Book an Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="row g-3 p-3">

                <!-- Full Name -->
                <div class="col-md-6">
                    <label class="form-label">Full Name *</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($owner_name) ?>" readonly>
                </div>

                <!-- Email -->
                <div class="col-md-6">
                    <label class="form-label">Email *</label>
                    <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                </div>

                <!-- Clinic -->
                <div class="col-md-6">
                    <label class="form-label">Select Branch *</label>
                    <select name="clinic_id" id="clinicSelect" class="form-select" required>
                        <option value="">Select a branch</option>
                        <?php foreach ($clinics as $clinic): ?>
                            <option value="<?= $clinic['clinic_id'] ?>">
                                <?= htmlspecialchars($clinic['clinic_name']) ?> -
                                <?= htmlspecialchars($clinic['address']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Service -->
                <div class="col-md-6">
                    <label class="form-label">Reason of Appointment *</label>
                    <select name="service_id" id="serviceSelect" class="form-select" required disabled>
                        <option value="">Please select a clinic first</option>
                    </select>
                </div>

                <div class="col-15">
                    <label class="form-label fw-semibold">Clinic Schedule</label>
                    <div id="clinicScheduleDisplay" class="clinic-sched-box">
                        Please select a clinic to view available schedules.
                    </div>
                </div>

                <!-- Residence -->
                <div class="col-md-6">
                    <label class="form-label">Area of Residence</label>
                    <input type="text" name="residence" class="form-control" placeholder="eg. Makawa, Aloran">
                </div>

                <!-- Appointment Date -->
                <div class="col-md-6">
                    <label class="form-label">Appointment Date *</label>
                    <input type="date" name="appointment_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                </div>

                <!-- Phone -->
                <div class="col-md-6">
                    <label class="form-label">Phone Number *</label>
                    <input type="text" name="phone" id="phone" class="form-control"
                        value="<?= htmlspecialchars($user['phone'] ?? '') ?>" maxlength="11" pattern="^09\d{9}$"
                        required>
                </div>

                <!-- Pet -->
                <div class="col-md-6">
                    <label class="form-label">Select Your Pet *</label>
                    <select name="pet_id" class="form-select" required>
                        <option value="">Select Pet</option>
                        <?php foreach ($pets as $pet): ?>
                            <option value="<?= $pet['pet_id'] ?>"><?= htmlspecialchars($pet['pet_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Doctor (Optional) -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold text-primary">
                        <i class="bi bi-person-badge"></i> Select Doctor (Optional)
                    </label>

                    <div class="input-group shadow-sm rounded">
                        <select id="doctorSelect" name="doctor_id" class="form-select border-start-0">
                            <option value="">Select Doctor</option>
                        </select>
                    </div>

                    <div id="doctorSpecialization" class="form-text mt-2 ps-2 text-secondary fst-italic"
                        style="display:none; font-size: 0.9rem;">
                        <!-- Specialization will show here -->
                    </div>

                    <div id="doctorScheduleDisplay" class="border rounded p-3 bg-light text-muted mt-2">
                        Select a doctor to view schedule.
                    </div>
                </div>

                <!-- Appointment Time Slot -->
                <div class="col-md-6 mt-3">
                    <label class="form-label">Select Time *</label>
                    <select name="appointment_start" id="timeSlot" class="form-select" required>
                        <option value="">Select Time</option>
                        <option value="08:00">01:00 AM - 02:00 AM</option>
                        <option value="08:00">02:00 AM - 03:00 AM</option>
                        <option value="08:00">03:00 AM - 04:00 AM</option>
                        <option value="08:00">04:00 AM - 05:00 AM</option>
                        <option value="08:00">05:00 AM - 06:00 AM</option>
                        <option value="08:00">06:00 AM - 07:00 AM</option>
                        <option value="08:00">07:00 AM - 08:00 AM</option>
                        <option value="08:00">08:00 AM - 09:00 AM</option>
                        <option value="09:00">09:00 AM - 10:00 AM</option>
                        <option value="10:00">10:00 AM - 11:00 AM</option>
                        <option value="11:00">11:00 AM - 12:00 PM</option>
                        <option value="13:00">01:00 PM - 02:00 PM</option>
                        <option value="14:00">02:00 PM - 03:00 PM</option>
                        <option value="15:00">03:00 PM - 04:00 PM</option>
                        <option value="16:00">04:00 PM - 05:00 PM</option>
                        <option value="16:00">05:00 PM - 06:00 PM</option>
                        <option value="16:00">06:00 PM - 07:00 PM</option>
                        <option value="16:00">07:00 PM - 08:00 PM</option>
                        <option value="16:00">08:00 PM - 09:00 PM</option>
                        <option value="16:00">09:00 PM - 10:00 PM</option>
                        <option value="16:00">10:00 PM - 11:00 PM</option>
                        <option value="16:00">11:00 PM - 12:00 PM</option>
                        <option value="16:00">12:00 AM - 01:00 PM</option>
                    </select>
                </div>

                <!-- Message -->
                <div class="col-12">
                    <label class="form-label">Message</label>
                    <textarea name="message" class="form-control" rows="3"></textarea>
                </div>

                <!-- Submit -->
                <div class="modal-footer">
                    <button type="submit" name="submit_booking" class="btn btn-success">
                        <i class="bi bi-calendar-plus"></i> Confirm Booking
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // When clinic changes, load doctors for that clinic
    document.getElementById('clinicSelect').addEventListener('change', function () {
        const clinicId = this.value;

        // Reset doctor fields
        document.getElementById('doctorSelect').innerHTML = '<option value="">Select Doctor</option>';
        document.getElementById('doctorSpecialization').style.display = 'none';
        document.getElementById('doctorScheduleDisplay').innerHTML = 'Select a doctor to view schedule.';

        if (clinicId === '') return;

        // Fetch doctors
        fetch('ajax_get_doctors.php?clinic_id=' + clinicId)
            .then(response => response.json())
            .then(data => {
                const doctorSelect = document.getElementById('doctorSelect');
                doctorSelect.innerHTML = '<option value="">Select Doctor</option>';

                data.forEach(doc => {
                    doctorSelect.innerHTML += `
                    <option value="${doc.staff_id}" data-specialization="${doc.specialization}">
                        ${doc.name}
                    </option>
                `;
                });
            });
    });

</script>
<style>
    /* Clinic schedule responsive box */
    .clinic-sched-box {
        border: 1px solid #dee2e6;
        background: #f8f9fa;
        border-radius: 6px;
        padding: 15px;
        min-height: 80px;
    }

    /* Mobile improvements */
    @media (max-width: 768px) {
        .clinic-sched-box {
            font-size: 0.9rem;
            padding: 12px;
        }

        #bookingModal .modal-dialog {
            margin: 10px;
        }

        #bookingModal .modal-content {
            border-radius: 10px;
        }

        #bookingModal .form-label {
            font-size: 0.9rem;
        }

        #bookingModal select,
        #bookingModal input,
        #bookingModal textarea {
            font-size: 0.9rem;
        }
    }

    @media (max-width: 768px) {
        .clinic-sched-box {
            max-height: 200px;
            overflow-y: auto;
        }
    }

    /* Reduce spacing inside booking modal on mobile */
    @media (max-width: 768px) {
        #bookingModal .row {
            --bs-gutter-x: 0.75rem !important;
            /* reduce left-right spacing */
        }

        #bookingModal .col-md-6,
        #bookingModal .col-12 {
            padding-left: 8px !important;
            padding-right: 8px !important;
        }


        .clinic-sched-box {
            font-size: 0.9rem;
            max-height: 140px;
            /* smaller on mobile */
            padding: 10px 12px;

        }

    }

    #bookingModal .row.g-3 {
        --bs-gutter-x: 0.5rem !important;
    }
</style>