function toggleEdit(showEdit) {
    if (showEdit) {
        document.getElementById('viewProfile').style.display = 'none';
        document.getElementById('editProfile').style.display = 'block';
    } else {
        document.getElementById('viewProfile').style.display = 'block';
        document.getElementById('editProfile').style.display = 'none';
    }
}