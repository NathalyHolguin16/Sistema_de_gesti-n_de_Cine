document.getElementById('formRegistroCliente').addEventListener('submit', async (e) => {
    e.preventDefault();

    const nombre = document.getElementById('nombre').value;
    const correo = document.getElementById('correo').value;
    const telefono = document.getElementById('telefono').value;
    const contrasena = document.getElementById('contrasena').value; // Nuevo campo

    const res = await fetch('../php/registro_cliente.php', {
        method: 'POST',
        body: JSON.stringify({ nombre, correo, telefono, contrasena }),
        headers: { 'Content-Type': 'application/json' }
    });

    // Manejar errores de respuesta no JSON
    try {
        const result = await res.json();
        if (result.success) {
            alert('¡Registro exitoso!');
            // Almacenar el cliente en localStorage con su ID
            localStorage.setItem('cliente', JSON.stringify({ id: result.id, nombre, correo }));
            window.location.href = '/html/index.html'; // Redirige al inicio
        } else {
            alert(result.error || 'Error al registrarse.');
        }
    } catch (error) {
        console.error('Error al procesar la respuesta:', error);
        alert('Error inesperado. Intente nuevamente más tarde.');
    }
});