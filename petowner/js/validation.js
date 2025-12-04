function validatePhone(input) {
    input.value = input.value.replace(/[^0-9]/g, '');

    const regex = /^09\d{9}$/;

    if (!regex.test(input.value)) {
        input.setCustomValidity("Invalid phone number. Must start with 09 and be 11 digits long.");
    } else {
        input.setCustomValidity("");
    }
}
