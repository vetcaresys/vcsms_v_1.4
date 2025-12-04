function validateStaffForm(form) {
    const contact = form.contact_number.value.trim();
    if (!/^09\d{9}$/.test(contact)) {
        alert("Contact number must start with 09 and be 11 digits.");
        return false;
    }

    const pass = form.password.value.trim();
    if (pass.length > 0 && !/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@$!%*#?&]{6,20}$/.test(pass)) {
        alert("Password must be 6–20 characters, with at least 1 letter & 1 number.");
        return false;
    }

    return true; // ✅ Pass all checks
}

didClose: () => {
    location.reload();
}