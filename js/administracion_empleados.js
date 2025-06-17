document.addEventListener('DOMContentLoaded', () => {
    const empleado = JSON.parse(localStorage.getItem('empleado'));

    if (!empleado || empleado.rol !== 'Administrador') {
        alert('Debes ser administrador para acceder a esta página.');
        window.location.href = 'administracion.html';
        return;
    }

    const formEmpleado = document.getElementById('formEmpleado');
    const empleadosTabla = document.getElementById('empleadosTabla');
    const btnCancelarEmpleado = document.getElementById('btnCancelarEmpleado');

    if (!empleadosTabla) {
        console.error('El elemento empleadosTabla no existe en el DOM.');
        return;
    }

    let editMode = false;
    let currentPageEmpleados = 1;
    const limitEmpleados = 10;

    // Cerrar sesión
    document.getElementById('logoutAdminBtn').addEventListener('click', () => {
        localStorage.removeItem('empleado');
        alert('Has cerrado sesión.');
        window.location.href = 'index.html';
    });

    // Cargar lista de empleados
    async function cargarEmpleados(page) {
        const res = await fetch(`../php/empleados.php?page=${page}&limit=${limitEmpleados}`);
        const data = await res.json();

        if (data.success) {
            empleadosTabla.innerHTML = '';
            data.data.forEach(emp => {
                empleadosTabla.innerHTML += `
                    <tr>
                        <td>${emp.nombre}</td>
                        <td>${emp.cargo}</td>
                        <td>${emp.usuario}</td>
                        <td>${emp.rol}</td>
                        <td>
                            <button onclick="editarEmpleado(${emp.id_empleado})">Editar</button>
                            <button onclick="eliminarEmpleado(${emp.id_empleado})">Eliminar</button>
                        </td>
                    </tr>
                `;
            });
        }
    }

    // Registrar o modificar empleado
    formEmpleado.addEventListener('submit', async (e) => {
        e.preventDefault();

        const id_empleado = document.getElementById('id_empleado').value;
        const nombre = document.getElementById('nombreEmpleado').value;
        const cargo = document.getElementById('cargoEmpleado').value;
        const usuario = document.getElementById('usuarioEmpleado').value;
        const contrasena = document.getElementById('contrasenaEmpleado').value;
        const rol = document.getElementById('rolEmpleado').value;
        const id_empleado_admin = empleado.id; // ID del administrador logueado

        const res = await fetch('../php/empleados.php', {
            method: id_empleado ? 'PUT' : 'POST',
            body: JSON.stringify({ id_empleado, nombre, cargo, usuario, contrasena, rol, id_empleado_admin }),
            headers: { 'Content-Type': 'application/json' }
        });

        const result = await res.json();
        if (result.success) {
            alert(id_empleado ? 'Empleado actualizado' : 'Empleado registrado');
            formEmpleado.reset();
            btnCancelarEmpleado.style.display = 'none';
            editMode = false;
            cargarEmpleados(currentPageEmpleados);
        } else {
            alert(result.error || 'Error al guardar empleado.');
        }
    });

    // Cancelar edición
    btnCancelarEmpleado.addEventListener('click', () => {
        formEmpleado.reset();
        btnCancelarEmpleado.style.display = 'none';
        editMode = false;
    });

    // Eliminar empleado
    window.eliminarEmpleado = async (id_empleado) => {
        if (!confirm('¿Estás seguro de eliminar este empleado?')) return;

        const res = await fetch(`../php/empleados.php?id_empleado=${id_empleado}`, {
            method: 'DELETE'
        });

        const result = await res.json();
        if (result.success) {
            alert('Empleado eliminado');
            cargarEmpleados(currentPageEmpleados);
        } else {
            alert(result.error || 'Error al eliminar empleado.');
        }
    };

    // Editar empleado
    window.editarEmpleado = (id_empleado) => {
        editMode = true;
        btnCancelarEmpleado.style.display = 'inline';

        // Cargar datos del empleado en el formulario
        fetch(`../php/empleados.php?id_empleado=${id_empleado}`)
            .then(res => res.json())
            .then(emp => {
                document.getElementById('id_empleado').value = emp.id_empleado;
                document.getElementById('nombreEmpleado').value = emp.nombre;
                document.getElementById('cargoEmpleado').value = emp.cargo;
                document.getElementById('usuarioEmpleado').value = emp.usuario;
                document.getElementById('rolEmpleado').value = emp.rol;
            });
    };

    // Gestionar películas
    async function gestionarPeliculas(modo, peliculaData) {
        const id_empleado_admin = empleado.id; // ID del administrador logueado
        peliculaData.id_empleado_admin = id_empleado_admin;

        const res = await fetch('../php/peliculas.php', {
            method: 'POST',
            body: JSON.stringify({ ...peliculaData, modo }),
            headers: { 'Content-Type': 'application/json' }
        });

        const result = await res.json();
        if (result.success) {
            alert(modo === 'agregar' ? 'Película agregada' : 'Película actualizada');
            cargarPeliculas();
        } else {
            alert(result.error || 'Error al gestionar película.');
        }
    }

    // Gestionar funciones
    async function gestionarFunciones(modo, funcionData) {
        const id_empleado_admin = empleado.id; // ID del administrador logueado
        funcionData.id_empleado_admin = id_empleado_admin;

        const res = await fetch('../php/funciones.php', {
            method: 'POST',
            body: JSON.stringify({ ...funcionData, modo }),
            headers: { 'Content-Type': 'application/json' }
        });

        const result = await res.json();
        if (result.success) {
            alert(modo === 'agregar' ? 'Función agregada' : 'Función actualizada');
            cargarFunciones();
        } else {
            alert(result.error || 'Error al gestionar función.');
        }
    }

    // Event listeners para paginación
    const prevPageEmpleados = document.getElementById('prevPageEmpleados');
    const nextPageEmpleados = document.getElementById('nextPageEmpleados');

    prevPageEmpleados.addEventListener('click', () => {
        if (currentPageEmpleados > 1) {
            currentPageEmpleados--;
            cargarEmpleados(currentPageEmpleados);
        }
    });

    nextPageEmpleados.addEventListener('click', () => {
        currentPageEmpleados++;
        cargarEmpleados(currentPageEmpleados);
    });

    // Inicializar
    cargarEmpleados(currentPageEmpleados);
});