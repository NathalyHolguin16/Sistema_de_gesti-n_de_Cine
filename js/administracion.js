// Variables globales para reportes
let currentPageReservas = 1;
const limitReservas = 10;
let paginationInfoReservas = {};

// Función global para cargar reportes de reservas
async function cargarReporteReservas(page = 1) {
    try {
        const res = await fetch(`../php/reportes.php?action=reservas&page=${page}&limit=${limitReservas}`);
        const data = await res.json();

        if (data.success) {
            currentPageReservas = page;

            const tablaReservas = document.getElementById('tablaReservas');
            if (tablaReservas) {
                tablaReservas.innerHTML = '';
                
                if (!data.data || !data.data.length) {
                    tablaReservas.innerHTML = '<tr><td colspan="6" class="mensaje-info">No hay reservas registradas.</td></tr>';
                } else {
                    data.data.forEach(reserva => {
                        tablaReservas.innerHTML += `
                            <tr>
                                <td>${reserva.cliente_nombre || 'Cliente'}</td>
                                <td>${reserva.pelicula_titulo || 'Película'}</td>
                                <td>${reserva.fecha || 'N/A'}</td>
                                <td>${reserva.hora || 'N/A'}</td>
                                <td>${reserva.num_asientos || '1'}</td>
                                <td>$${reserva.total_pagado || '0.00'}</td>
                            </tr>`;
                    });
                }

                // Simular controles de paginación básicos
                paginationInfoReservas = {
                    current_page: page,
                    total_pages: 1,
                    has_prev_page: false,
                    has_next_page: false
                };

                // Intentar actualizar controles si la función existe
                if (typeof actualizarControlesPaginacionReservas === 'function') {
                    actualizarControlesPaginacionReservas();
                }
            }
        } else {
            console.error('Error en la respuesta:', data);
            const tablaReservas = document.getElementById('tablaReservas');
            if (tablaReservas) {
                tablaReservas.innerHTML = '<tr><td colspan="6" class="mensaje-error">Error al cargar reservas</td></tr>';
            }
        }
    } catch (error) {
        console.error("Error al cargar reporte:", error);
        const tablaReservas = document.getElementById('tablaReservas');
        if (tablaReservas) {
            tablaReservas.innerHTML = '<tr><td colspan="6" class="mensaje-error">Error al cargar reporte</td></tr>';
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Verificar autenticación con el nuevo sistema unificado
    const usuario = JSON.parse(sessionStorage.getItem('usuario') || 'null');
    const tipoUsuario = sessionStorage.getItem('tipoUsuario');

    // Verificar que sea un empleado
    if (!usuario || tipoUsuario !== 'empleado') {
        alert('Debes iniciar sesión como empleado para acceder a esta página.');
        window.location.href = 'login_unificado.html';
        return;
    }

    // Mostrar información del usuario en el dashboard
    const adminNameEl = document.getElementById('adminName');
    if (adminNameEl) {
        adminNameEl.textContent = usuario.nombre || 'Administrador';
    }
    
    // Mostrar opciones según el rol
    if (usuario.rol === 'Administrador') {
        const navEmpleadosEl = document.getElementById('navEmpleados');
        const btnEmpleadosEl = document.getElementById('btnEmpleados');
        if (navEmpleadosEl) navEmpleadosEl.style.display = 'inline';
        if (btnEmpleadosEl) btnEmpleadosEl.style.display = 'block';
    } else if (usuario.rol === 'Empleado') {
        const navEmpleadosEl = document.getElementById('navEmpleados');
        const btnEmpleadosEl = document.getElementById('btnEmpleados');
        if (navEmpleadosEl) navEmpleadosEl.style.display = 'none';
        if (btnEmpleadosEl) btnEmpleadosEl.style.display = 'none';
    }

    // Cargar estadísticas del dashboard
    cargarEstadisticasDashboard();

    // Cerrar sesión
    const logoutBtn = document.getElementById('logoutAdminBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async () => {
            try {
                // Obtener datos de la sesión antes de limpiarla
                const usuario = JSON.parse(sessionStorage.getItem('usuario') || 'null');
                const tipoUsuario = sessionStorage.getItem('tipoUsuario');
                
                if (usuario && tipoUsuario) {
                    // Enviar solicitud de logout al servidor para registrar en bitácora
                    const response = await fetch('../php/login_unificado.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'logout',
                            usuario: usuario,
                            tipo: tipoUsuario
                        })
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        console.log('Logout registrado en bitácora');
                    } else {
                        console.warn('Error al registrar logout:', result.error);
                    }
                }
            } catch (error) {
                console.error('Error en logout:', error);
            } finally {
                // Limpiar la nueva estructura de datos del sistema unificado
                sessionStorage.removeItem('usuario');
                sessionStorage.removeItem('tipoUsuario');
                sessionStorage.removeItem('clientInfo');
                
                alert('Has cerrado sesión.');
                window.location.href = 'login_unificado.html';
            }
        });
    }

    // Cargar estadísticas básicas del dashboard
    async function cargarEstadisticasDashboard() {
        try {
            // Cargar total de películas
            const peliculasRes = await fetch('../php/peliculas.php?limit=1000');
            const peliculasData = await peliculasRes.json();
            if (peliculasData.success) {
                const totalPeliculasEl = document.getElementById('totalPeliculas');
                if (totalPeliculasEl) {
                    totalPeliculasEl.textContent = peliculasData.pagination.total_items;
                }
            }

            // Cargar estadísticas de reservas, clientes e ingresos
            const statsRes = await fetch('../php/reportes.php?action=dashboard_stats');
            const statsData = await statsRes.json();
            if (statsData.success) {
                const reservasHoyEl = document.getElementById('reservasHoy');
                const totalClientesEl = document.getElementById('totalClientes');
                const ingresosMesEl = document.getElementById('ingresosMes');
                
                if (reservasHoyEl) reservasHoyEl.textContent = statsData.reservas_hoy || '0';
                if (totalClientesEl) totalClientesEl.textContent = statsData.total_clientes || '0';
                if (ingresosMesEl) ingresosMesEl.textContent = '$' + (statsData.ingresos_mes || '0');
            }
        } catch (error) {
            console.error('Error cargando estadísticas:', error);
            // Valores por defecto en caso de error
            const totalPeliculasEl = document.getElementById('totalPeliculas');
            const reservasHoyEl = document.getElementById('reservasHoy');
            const totalClientesEl = document.getElementById('totalClientes');
            const ingresosMesEl = document.getElementById('ingresosMes');
            
            if (totalPeliculasEl) totalPeliculasEl.textContent = '0';
            if (reservasHoyEl) reservasHoyEl.textContent = '0';
            if (totalClientesEl) totalClientesEl.textContent = '0';
            if (ingresosMesEl) ingresosMesEl.textContent = '$0';
        }
    }

    let currentPagePeliculas = 1;
    const limitPeliculas = 10;
    let paginationInfoPeliculas = {};

    async function cargarPeliculas(page = 1) {
        try {
            const res = await fetch(`../php/peliculas.php?page=${page}&limit=${limitPeliculas}`);
            const data = await res.json();

            if (data.success) {
                paginationInfoPeliculas = data.pagination;
                currentPagePeliculas = paginationInfoPeliculas.current_page;

                // Verificar que el elemento existe antes de intentar modificarlo
                const peliculasTabla = document.getElementById('peliculasTabla');
                if (peliculasTabla) {
                    peliculasTabla.innerHTML = '';
                    
                    if (!data.data.length) {
                        peliculasTabla.innerHTML = '<tr><td colspan="2" class="mensaje-info">No hay películas registradas.</td></tr>';
                    } else {
                        data.data.forEach(pelicula => {
                            peliculasTabla.innerHTML += `<tr><td>${pelicula.titulo}</td><td>${pelicula.genero}</td></tr>`;
                        });
                    }

                    actualizarControlesPaginacionPeliculas();
                } else {
                    console.warn('Elemento peliculasTabla no encontrado en el DOM');
                }
            }
        } catch (error) {
            console.error("Error al cargar películas:", error);
            const peliculasTabla = document.getElementById('peliculasTabla');
            if (peliculasTabla) {
                peliculasTabla.innerHTML = '<tr><td colspan="2" class="mensaje-error">Error al cargar películas</td></tr>';
            }
        }
    }

    // Función para actualizar los controles de paginación de películas
    function actualizarControlesPaginacionPeliculas() {
        const prevBtn = document.getElementById('prevPagePeliculas');
        const nextBtn = document.getElementById('nextPagePeliculas');
        const pageInfo = document.getElementById('pageInfoPeliculasAdmin');

        if (prevBtn) {
            prevBtn.disabled = !paginationInfoPeliculas.has_prev_page;
            prevBtn.style.opacity = paginationInfoPeliculas.has_prev_page ? '1' : '0.5';
        }

        if (nextBtn) {
            nextBtn.disabled = !paginationInfoPeliculas.has_next_page;
            nextBtn.style.opacity = paginationInfoPeliculas.has_next_page ? '1' : '0.5';
        }

        if (pageInfo) {
            pageInfo.textContent = `Página ${paginationInfoPeliculas.current_page || 1} de ${paginationInfoPeliculas.total_pages || 1}`;
        }
    }

    // Event listeners para paginación
    const prevPagePeliculas = document.getElementById('prevPagePeliculas');
    const nextPagePeliculas = document.getElementById('nextPagePeliculas');

    if (prevPagePeliculas) {
        prevPagePeliculas.addEventListener('click', () => {
            if (paginationInfoPeliculas.has_prev_page && currentPagePeliculas > 1) {
                cargarPeliculas(currentPagePeliculas - 1);
            }
        });
    }

    if (nextPagePeliculas) {
        nextPagePeliculas.addEventListener('click', () => {
            if (paginationInfoPeliculas.has_next_page) {
                cargarPeliculas(currentPagePeliculas + 1);
            }
        });
    }

    // Función para actualizar los controles de paginación de reservas
    function actualizarControlesPaginacionReservas() {
        const prevBtn = document.getElementById('prevPageReservas');
        const nextBtn = document.getElementById('nextPageReservas');
        const pageInfo = document.getElementById('pageInfoReservas');

        if (prevBtn) {
            prevBtn.disabled = !paginationInfoReservas.has_prev_page;
            prevBtn.style.opacity = paginationInfoReservas.has_prev_page ? '1' : '0.5';
        }

        if (nextBtn) {
            nextBtn.disabled = !paginationInfoReservas.has_next_page;
            nextBtn.style.opacity = paginationInfoReservas.has_next_page ? '1' : '0.5';
        }

        if (pageInfo) {
            pageInfo.textContent = `Página ${paginationInfoReservas.current_page || 1} de ${paginationInfoReservas.total_pages || 1}`;
        }
    }

    // Event listeners para paginación de reservas
    const prevPageReservas = document.getElementById('prevPageReservas');
    const nextPageReservas = document.getElementById('nextPageReservas');

    if (prevPageReservas) {
        prevPageReservas.addEventListener('click', () => {
            if (paginationInfoReservas.has_prev_page && currentPageReservas > 1) {
                cargarReporteReservas(currentPageReservas - 1);
            }
        });
    }

    if (nextPageReservas) {
        nextPageReservas.addEventListener('click', () => {
            if (paginationInfoReservas.has_next_page) {
                cargarReporteReservas(currentPageReservas + 1);
            }
        });
    }

    // Cargar el reporte de reservas al iniciar (solo si la sección es visible)
    cargarReporteReservas();
    
    // Solo cargar películas si hay una sección de películas visible
    const peliculasTabla = document.getElementById('peliculasTabla');
    if (peliculasTabla) {
        cargarPeliculas(currentPagePeliculas);
    }
});

// ===== FUNCIONES DEL NUEVO DASHBOARD =====

// Función para mostrar el dashboard principal
function mostrarDashboard() {
    const adminContentEl = document.getElementById('adminContent');
    const reporteReservasEl = document.getElementById('reporteReservas');
    const bitacoraClientesEl = document.getElementById('bitacoraClientesSection');
    const bitacoraEmpleadosEl = document.getElementById('bitacoraEmpleadosSection');
    
    if (adminContentEl) adminContentEl.style.display = 'block';
    if (reporteReservasEl) reporteReservasEl.style.display = 'none';
    if (bitacoraClientesEl) bitacoraClientesEl.style.display = 'none';
    if (bitacoraEmpleadosEl) bitacoraEmpleadosEl.style.display = 'none';
}

// Función para mostrar la sección de reportes
function mostrarReportes() {
    const adminContentEl = document.getElementById('adminContent');
    const reporteReservasEl = document.getElementById('reporteReservas');
    const bitacoraClientesEl = document.getElementById('bitacoraClientesSection');
    const bitacoraEmpleadosEl = document.getElementById('bitacoraEmpleadosSection');
    
    if (adminContentEl) adminContentEl.style.display = 'none';
    if (reporteReservasEl) reporteReservasEl.style.display = 'block';
    if (bitacoraClientesEl) bitacoraClientesEl.style.display = 'none';
    if (bitacoraEmpleadosEl) bitacoraEmpleadosEl.style.display = 'none';
    
    // Cargar reportes si no están cargados
    cargarReporteReservas();
}

// Función para mostrar la bitácora de clientes
function mostrarBitacoraClientes() {
    const adminContentEl = document.getElementById('adminContent');
    const reporteReservasEl = document.getElementById('reporteReservas');
    const bitacoraClientesEl = document.getElementById('bitacoraClientesSection');
    const bitacoraEmpleadosEl = document.getElementById('bitacoraEmpleadosSection');
    
    if (adminContentEl) adminContentEl.style.display = 'none';
    if (reporteReservasEl) reporteReservasEl.style.display = 'none';
    if (bitacoraClientesEl) bitacoraClientesEl.style.display = 'block';
    if (bitacoraEmpleadosEl) bitacoraEmpleadosEl.style.display = 'none';
    
    // Cargar bitácora de clientes
    cargarBitacoraClientes();
}

// Función para mostrar la bitácora de empleados
function mostrarBitacoraEmpleados() {
    const adminContentEl = document.getElementById('adminContent');
    const reporteReservasEl = document.getElementById('reporteReservas');
    const bitacoraClientesEl = document.getElementById('bitacoraClientesSection');
    const bitacoraEmpleadosEl = document.getElementById('bitacoraEmpleadosSection');
    
    if (adminContentEl) adminContentEl.style.display = 'none';
    if (reporteReservasEl) reporteReservasEl.style.display = 'none';
    if (bitacoraClientesEl) bitacoraClientesEl.style.display = 'none';
    if (bitacoraEmpleadosEl) bitacoraEmpleadosEl.style.display = 'block';
    
    // Cargar bitácora de empleados
    cargarBitacoraEmpleados();
}

// Función para cargar la bitácora de clientes
async function cargarBitacoraClientes() {
    try {
        const filtroFechaEl = document.getElementById('filtroBitacoraClientesFecha');
        const filtroNombreEl = document.getElementById('filtroBitacoraClientesNombre');
        
        const filtroFecha = filtroFechaEl ? filtroFechaEl.value : '';
        const filtroNombre = filtroNombreEl ? filtroNombreEl.value : '';
        
        let url = '../php/bitacora_clientes.php';
        const params = new URLSearchParams();
        
        if (filtroFecha) {
            params.append('fecha_inicio', filtroFecha);
            params.append('fecha_fin', filtroFecha);
        }
        
        if (filtroNombre) {
            params.append('cliente_nombre', filtroNombre);
        }
        
        if (params.toString()) {
            url += '?' + params.toString();
        }

        const response = await fetch(url);
        const data = await response.json();

        const tablaBitacoraClientes = document.getElementById('tablaBitacoraClientes');
        if (!tablaBitacoraClientes) {
            console.warn('Elemento tablaBitacoraClientes no encontrado');
            return;
        }
        
        tablaBitacoraClientes.innerHTML = '';

        if (data.success && data.data && data.data.length > 0) {
            data.data.forEach(registro => {
                const row = document.createElement('tr');
                
                // Formatear fecha
                const fecha = new Date(registro.fecha_hora).toLocaleString('es-ES');
                
                // Información de reserva (si existe)
                const peliculaInfo = registro.nombre_pelicula || '-';
                const funcionInfo = registro.fecha_funcion && registro.hora_funcion 
                    ? `${registro.fecha_funcion} ${registro.hora_funcion}${registro.sala ? ` (Sala ${registro.sala})` : ''}`
                    : '-';
                const asientosInfo = registro.asientos_reservados 
                    ? `${registro.cantidad_asientos || 0} asientos: ${registro.asientos_reservados}`
                    : '-';
                const totalInfo = registro.total_pagado ? `$${parseFloat(registro.total_pagado).toFixed(2)}` : '-';
                
                // Determinar clase CSS según el tipo de acción
                const accionClass = registro.accion.toLowerCase().includes('reserva') ? 'accion-reserva' : 'accion-normal';
                
                row.innerHTML = `
                    <td>${fecha}</td>
                    <td>${registro.cliente_nombre || 'Cliente'}</td>
                    <td><span class="badge ${accionClass}">${registro.accion}</span></td>
                    <td class="pelicula-info">${peliculaInfo}</td>
                    <td class="funcion-info">${funcionInfo}</td>
                    <td class="asientos-info">${asientosInfo}</td>
                    <td class="total-info">${totalInfo}</td>
                    <td class="ip-info">${registro.ip_address || 'N/A'}</td>
                    <td class="detalles-info" title="${registro.detalles || ''}">${(registro.detalles || '').substring(0, 50)}${(registro.detalles || '').length > 50 ? '...' : ''}</td>
                `;
                tablaBitacoraClientes.appendChild(row);
            });
        } else {
            tablaBitacoraClientes.innerHTML = '<tr><td colspan="9" style="text-align: center; color: #666;">No hay registros de bitácora de clientes</td></tr>';
        }
    } catch (error) {
        console.error('Error cargando bitácora de clientes:', error);
        const tablaBitacoraClientes = document.getElementById('tablaBitacoraClientes');
        if (tablaBitacoraClientes) {
            tablaBitacoraClientes.innerHTML = '<tr><td colspan="9" style="text-align: center; color: #e50914;">Error al cargar bitácora</td></tr>';
        }
    }
}

// Función para cargar la bitácora de empleados
async function cargarBitacoraEmpleados() {
    try {
        const filtroFechaEl = document.getElementById('filtroBitacoraEmpleadosFecha');
        const filtroNombreEl = document.getElementById('filtroBitacoraEmpleadosNombre');
        
        const filtroFecha = filtroFechaEl ? filtroFechaEl.value : '';
        const filtroNombre = filtroNombreEl ? filtroNombreEl.value : '';
        
        let url = '../php/bitacora_empleados.php';
        const params = new URLSearchParams();
        
        if (filtroFecha) {
            params.append('fecha_inicio', filtroFecha);
            params.append('fecha_fin', filtroFecha);
        }
        
        if (filtroNombre) {
            params.append('empleado_nombre', filtroNombre);
        }
        
        if (params.toString()) {
            url += '?' + params.toString();
        }

        const response = await fetch(url);
        const data = await response.json();

        const tablaBitacoraEmpleados = document.getElementById('tablaBitacoraEmpleados');
        if (!tablaBitacoraEmpleados) {
            console.warn('Elemento tablaBitacoraEmpleados no encontrado');
            return;
        }
        
        tablaBitacoraEmpleados.innerHTML = '';

        if (data.success && data.data && data.data.length > 0) {
            data.data.forEach(registro => {
                const row = document.createElement('tr');
                
                // Formatear fecha
                const fecha = new Date(registro.fecha_hora).toLocaleString('es-ES');
                
                // Determinar clase CSS según el tipo de acción
                let accionClass = 'accion-normal';
                if (registro.accion.toLowerCase().includes('login')) accionClass = 'accion-login';
                else if (registro.accion.toLowerCase().includes('logout')) accionClass = 'accion-logout';
                else if (registro.accion.toLowerCase().includes('error')) accionClass = 'accion-error';
                
                row.innerHTML = `
                    <td>${fecha}</td>
                    <td>${registro.empleado_nombre || 'Empleado'}</td>
                    <td><span class="badge badge-cargo">${registro.cargo || 'N/A'}</span></td>
                    <td><span class="badge ${accionClass}">${registro.accion}</span></td>
                    <td class="ip-info">${registro.ip_address || 'N/A'}</td>
                    <td class="detalles-info" title="${registro.detalles || ''}">${(registro.detalles || '').substring(0, 50)}${(registro.detalles || '').length > 50 ? '...' : ''}</td>
                `;
                tablaBitacoraEmpleados.appendChild(row);
            });
        } else {
            tablaBitacoraEmpleados.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #666;">No hay registros de bitácora de empleados</td></tr>';
        }
    } catch (error) {
        console.error('Error cargando bitácora de empleados:', error);
        const tablaBitacoraEmpleados = document.getElementById('tablaBitacoraEmpleados');
        if (tablaBitacoraEmpleados) {
            tablaBitacoraEmpleados.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #e50914;">Error al cargar bitácora</td></tr>';
        }
    }
}