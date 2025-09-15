function togglePassword() {
    const passwordField = document.getElementById('senha');
    const toggleIcon = document.querySelector('.toggle-password');
    
    if(passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleIcon.textContent = '👁️';
    } else {
        passwordField.type = 'password';
        toggleIcon.textContent = '👁️';
    }
}