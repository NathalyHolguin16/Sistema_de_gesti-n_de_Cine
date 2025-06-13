document.getElementById('formRegistroCliente').addEventListener('submit', async (e) => {
    e.preventDefault();

    const nombre = document.getElementById('nombre').value;
    const correo = document.getElementById('correo').value;
    const telefono = document.getElementById('telefono').value;

    const res = await fetch('../php/registro_cliente.php', {
        method: 'POST',
        body: JSON.stringify({ nombre, correo, telefono }),
        headers: { 'Content-Type': 'application/json' }
    });

    const result = await res.json();
    if (result.success) {
        alert('¡Registro exitoso!');
        // Almacenar el cliente en localStorage
        localStorage.setItem('cliente', JSON.stringify({ nombre, correo }));
        window.location.href = 'index.html'; // Redirige al inicio
    } else {
        alert(result.error || 'Error al registrarse.');
    }
});