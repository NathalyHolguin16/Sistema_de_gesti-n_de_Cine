document.addEventListener('DOMContentLoaded', () => {
    const empleado = JSON.parse(localStorage.getItem('empleado'));

    if (!empleado) {
        alert('Debes iniciar sesión como empleado para acceder a esta página.');
        window.location.href = 'login_cliente.html';
        return;
    }

    // Mostrar opciones según el rol
    if (empleado.rol === 'Administrador') {
        document.getElementById('navEmpleados').style.display = 'inline';
    } else if (empleado.rol === 'Empleado') {
        document.getElementById('navEmpleados').style.display = 'none';
    }

    // Cerrar sesión
    document.getElementById('logoutAdminBtn').addEventListener('click', () => {
        localStorage.removeItem('empleado');
        alert('Has cerrado sesión.');
        window.location.href = 'index.html';
    });
});