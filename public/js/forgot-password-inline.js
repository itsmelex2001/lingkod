// forgot-password-inline.js
async function requestCode() {
    const user = document.getElementById('recUser').value;
    const mobile = document.getElementById('recMobile').value;
    if (!user || !mobile) return alert('Please enter both username and mobile number.');

    try {
        const res = await fetch('/sdn/api/recover.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'request_code', username: user, mobile: mobile })
        });
        const result = await res.json();
        if (result.success) {
            alert(result.message + "\n\n(SIMULATION: Your code is " + result.mock_code + ")");
            document.getElementById('step1').style.display = 'none';
            document.getElementById('step2').style.display = 'block';
        } else {
            alert(result.message);
        }
    } catch (e) {
        alert('Error connecting to server.');
    }
}

async function resetPassword() {
    const user = document.getElementById('recUser').value;
    const code = document.getElementById('recCode').value;
    const pass = document.getElementById('recPass').value;
    if (!code || !pass) return alert('Please enter the code and your new password.');

    try {
        const res = await fetch('/sdn/api/recover.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'reset_password', username: user, code: code, new_password: pass })
        });
        const result = await res.json();
        if (result.success) {
            alert(result.message);
            window.location.href = '/sdn/pages/login.php';
        } else {
            alert(result.message);
        }
    } catch (e) {
        alert('Error connecting to server.');
    }
}
