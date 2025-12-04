<!-- inquiry form -->
<section id="contact" class="py-5">
    <div class="container">
        <h2 class="text-center mb-4">Have a Question?</h2>
        <p class="text-center text-muted mb-4">Send us your inquiry and we'll get back to you soon!</p>
        <form action="submit_inquiry.php" method="POST" class="mx-auto p-4 shadow rounded">
            <div class="mb-3">
                <label for="name" class="form-label">Your Name</label>
                <input type="text" name="name" id="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="subject" class="form-label">Subject</label>
                <input type="text" name="subject" id="subject" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="message" class="form-label">Message</label>
                <textarea name="message" id="message" class="form-control" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Send Inquiry</button>
        </form>
    </div>
</section>