document.addEventListener('DOMContentLoaded', () => {
    // Verificar autenticación con el nuevo sistema unificado
    const usuario = JSON.parse(sessionStorage.getItem('usuario') || 'null');
    const tipoUsuario = sessionStorage.getItem('tipoUsuario');

    // Verificar que sea un empleado con rol de Administrador
    if (!usuario || tipoUsuario !== 'empleado' || usuario.rol !== 'Administrador') {
        alert('Debes ser administrador para acceder a esta página.');
        window.location.href = 'login_unificado.html';
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
    let paginationInfoEmpleados = {};

    // Cerrar sesión
    document.getElementById('logoutAdminBtn').addEventListener('click', () => {
        // Limpiar la nueva estructura de datos del sistema unificado
        sessionStorage.removeItem('usuario');
        sessionStorage.removeItem('tipoUsuario');
        sessionStorage.removeItem('clientInfo');
        
        alert('Has cerrado sesión.');
        window.location.href = 'login_unificado.html';
    });

    // Cargar lista de empleados
    async function cargarEmpleados(page = 1) {
        try {
            const res = await fetch(`../php/empleados.php?page=${page}&limit=${limitEmpleados}`);
            const data = await res.json();

            if (data.success) {
                paginationInfoEmpleados = data.pagination;
                currentPageEmpleados = paginationInfoEmpleados.current_page;

                empleadosTabla.innerHTML = '';
                
                if (!data.data.length) {
                    empleadosTabla.innerHTML = '<tr><td colspan="5" class="mensaje-info">No hay empleados registrados.</td></tr>';
                    actualizarControlesPaginacionEmpleados();
                    return;
                }

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

                actualizarControlesPaginacionEmpleados();
            } else {
                console.error("Error al cargar empleados:", data.error);
                empleadosTabla.innerHTML = '<tr><td colspan="5" class="mensaje-error">Error al cargar empleados</td></tr>';
            }
        } catch (error) {
            console.error("Error en la petición:", error);
            empleadosTabla.innerHTML = '<tr><td colspan="5" class="mensaje-error">Error de conexión</td></tr>';
        }
    }

    // Función para actualizar los controles de paginación de empleados
    function actualizarControlesPaginacionEmpleados() {
        const prevBtn = document.getElementById('prevPageEmpleados');
        const nextBtn = document.getElementById('nextPageEmpleados');
        const pageInfo = document.getElementById('pageInfoEmpleados');

        if (prevBtn) {
            prevBtn.disabled = !paginationInfoEmpleados.has_prev_page;
            prevBtn.style.opacity = paginationInfoEmpleados.has_prev_page ? '1' : '0.5';
        }

        if (nextBtn) {
            nextBtn.disabled = !paginationInfoEmpleados.has_next_page;
            nextBtn.style.opacity = paginationInfoEmpleados.has_next_page ? '1' : '0.5';
        }

        if (pageInfo) {
            pageInfo.textContent = `Página ${paginationInfoEmpleados.current_page || 1} de ${paginationInfoEmpleados.total_pages || 1} (${paginationInfoEmpleados.total_items || 0} empleados)`;
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

    if (prevPageEmpleados) {
        prevPageEmpleados.addEventListener('click', () => {
            if (paginationInfoEmpleados.has_prev_page && currentPageEmpleados > 1) {
                cargarEmpleados(currentPageEmpleados - 1);
            }
        });
    }

    if (nextPageEmpleados) {
        nextPageEmpleados.addEventListener('click', () => {
            if (paginationInfoEmpleados.has_next_page) {
                cargarEmpleados(currentPageEmpleados + 1);
            }
        });
    }

    // Función para ir a una página específica de empleados
    function irAPaginaEmpleados(page) {
        if (page >= 1 && page <= (paginationInfoEmpleados.total_pages || 1)) {
            cargarEmpleados(page);
        }
    }

    // Hacer la función disponible globalmente
    window.irAPaginaEmpleados = irAPaginaEmpleados;

    // Inicializar
    cargarEmpleados(currentPageEmpleados);
});