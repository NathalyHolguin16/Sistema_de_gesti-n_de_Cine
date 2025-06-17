const formLogin = document.getElementById('formLogin');
const clienteFields = document.getElementById('clienteFields');
const empleadoFields = document.getElementById('empleadoFields');
const loginTitle = document.getElementById('loginTitle');
const toggleLoginBtn = document.getElementById('toggleLoginBtn');

let isEmpleadoLogin = false;

// Alternar entre cliente y empleado
toggleLoginBtn.addEventListener('click', () => {
    isEmpleadoLogin = !isEmpleadoLogin;

    if (isEmpleadoLogin) {
        clienteFields.style.display = 'none';
        empleadoFields.style.display = 'block';
        loginTitle.textContent = 'Login de Empleado';
        toggleLoginBtn.textContent = 'Iniciar sesión como cliente';
    } else {
        clienteFields.style.display = 'block';
        empleadoFields.style.display = 'none';
        loginTitle.textContent = 'Login de Cliente';
        toggleLoginBtn.textContent = 'Iniciar sesión como empleado';
    }
});

// Manejar el envío del formulario
formLogin.addEventListener('submit', async (e) => {
    e.preventDefault();

    // Eliminar 'required' de los campos ocultos
    if (isEmpleadoLogin) {
        document.getElementById('correoLogin').removeAttribute('required');
    } else {
        document.getElementById('usuarioLogin').removeAttribute('required');
        document.getElementById('contrasenaEmpleadoLogin').removeAttribute('required');
    }

    if (isEmpleadoLogin) {
        const usuario = document.getElementById('usuarioLogin').value;
        const contrasena = document.getElementById('contrasenaEmpleadoLogin').value;

        if (!usuario || !contrasena) {
            alert('Por favor, completa todos los campos para iniciar sesión como empleado.');
            return;
        }

        const res = await fetch('../php/login_empleado.php', {
            method: 'POST',
            body: JSON.stringify({ usuario, contrasena }),
            headers: { 'Content-Type': 'application/json' }
        });

        const result = await res.json();
        if (result.success) {
            alert('¡Login exitoso como empleado!');
            localStorage.setItem('empleado', JSON.stringify({ id: result.id, nombre: result.nombre, rol: result.rol }));
            window.location.href = 'index.html'; // Redirige al inicio
        } else {
            alert(result.error || 'Error al iniciar sesión como empleado.');
        }
    } else {
        const correo = document.getElementById('correoLogin').value;
        const contrasena = document.getElementById('contrasenaLogin').value; // Nuevo campo

        if (!correo || !contrasena) {
            alert('Por favor, completa todos los campos para iniciar sesión como cliente.');
            return;
        }

        const res = await fetch('../php/login_cliente.php', {
            method: 'POST',
            body: JSON.stringify({ correo, contrasena }),
            headers: { 'Content-Type': 'application/json' }
        });

        const result = await res.json();
        if (result.success) {
            alert('¡Login exitoso como cliente!');
            // Almacenar el cliente en localStorage con su ID
            localStorage.setItem('cliente', JSON.stringify({ id: result.id, nombre: result.nombre, correo }));
            window.location.href = 'index.html'; // Redirige al inicio
        } else {
            alert(result.error || 'Error al iniciar sesión como cliente.');
        }
    }
});

// Improved session closure registration to handle browser limitations
window.addEventListener('beforeunload', async (e) => {
    const cliente = JSON.parse(localStorage.getItem('cliente'));
    const empleado = JSON.parse(localStorage.getItem('empleado'));

    if (cliente) {
        navigator.sendBeacon('../php/bitacora_clientes.php', JSON.stringify({
            id_cliente: cliente.id,
            accion: 'Cierre de sesión',
            detalles: `El cliente con ID ${cliente.id} cerró sesión.`
        }));
    }

    if (empleado) {
        navigator.sendBeacon('../php/bitacora_empleados.php', JSON.stringify({
            id_empleado: empleado.id,
            accion: 'Cierre de sesión',
            detalles: `El empleado con ID ${empleado.id} cerró sesión.`
        }));
    }
});