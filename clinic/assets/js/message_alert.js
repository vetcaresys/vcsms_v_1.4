
// Check URL parameter
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('sent') === '1') {
    Swal.fire({
        title: "Message Sent!",
        text: "Your inquiry has been successfully submitted.",
        icon: "success",
        confirmButtonColor: "#0d6efd"
    });
}
