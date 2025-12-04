document.getElementById('toggleAddStaffPassword').addEventListener('click', function () {
    const pwField = document.getElementById('addStaffPassword');
    const isHidden = pwField.type === 'password';
    pwField.type = isHidden ? 'text' : 'password';
    this.textContent = isHidden ? 'Hide' : 'Show';
});

document.getElementById('toggleEditStaffPassword').addEventListener('click', function () {
    const pwField = document.getElementById('editStaffPassword');
    const isHidden = pwField.type === 'password';
    pwField.type = isHidden ? 'text' : 'password';
    this.textContent = isHidden ? 'Hide' : 'Show';
});