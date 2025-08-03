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

    // Mostrar opciones según el rol
    if (usuario.rol === 'Administrador') {
        document.getElementById('navEmpleados').style.display = 'inline';
    } else if (usuario.rol === 'Empleado') {
        document.getElementById('navEmpleados').style.display = 'none';
    }

    // Cerrar sesión
    document.getElementById('logoutAdminBtn').addEventListener('click', () => {
        // Limpiar la nueva estructura de datos del sistema unificado
        sessionStorage.removeItem('usuario');
        sessionStorage.removeItem('tipoUsuario');
        sessionStorage.removeItem('clientInfo');
        
        alert('Has cerrado sesión.');
        window.location.href = 'login_unificado.html';
    });

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

                const peliculasTabla = document.getElementById('peliculasTabla');
                peliculasTabla.innerHTML = '';
                
                if (!data.data.length) {
                    peliculasTabla.innerHTML = '<tr><td colspan="2" class="mensaje-info">No hay películas registradas.</td></tr>';
                } else {
                    data.data.forEach(pelicula => {
                        peliculasTabla.innerHTML += `<tr><td>${pelicula.titulo}</td><td>${pelicula.genero}</td></tr>`;
                    });
                }

                actualizarControlesPaginacionPeliculas();
            }
        } catch (error) {
            console.error("Error al cargar películas:", error);
            document.getElementById('peliculasTabla').innerHTML = '<tr><td colspan="2" class="mensaje-error">Error al cargar películas</td></tr>';
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

    let currentPageReservas = 1;
    const limitReservas = 10;
    let paginationInfoReservas = {};

    async function cargarReporteReservas(page = 1) {
        try {
            const res = await fetch(`../php/reportes.php?page=${page}&limit=${limitReservas}`);
            const data = await res.json();

            if (data.success) {
                paginationInfoReservas = data.pagination;
                currentPageReservas = paginationInfoReservas.current_page;

                const tablaReservas = document.getElementById('tablaReservas');
                tablaReservas.innerHTML = '';
                
                if (!data.data.length) {
                    tablaReservas.innerHTML = '<tr><td colspan="6" class="mensaje-info">No hay reservas registradas.</td></tr>';
                } else {
                    data.data.forEach(reserva => {
                        tablaReservas.innerHTML += `
                            <tr>
                                <td>${reserva.cliente}</td>
                                <td>${reserva.pelicula}</td>
                                <td>${reserva.fecha}</td>
                                <td>${reserva.hora}</td>
                                <td>${reserva.asientos}</td>
                                <td>$${parseFloat(reserva.total_pagado).toFixed(2)}</td>
                            </tr>`;
                    });
                }

                // Mostrar resumen si está disponible
                if (data.summary) {
                    const summaryDiv = document.getElementById('reservasSummary');
                    if (summaryDiv) {
                        summaryDiv.innerHTML = `
                            <div class="summary-item">Total entradas: ${data.summary.total_entradas}</div>
                            <div class="summary-item">Ingresos totales: $${parseFloat(data.summary.ingresos_totales).toFixed(2)}</div>
                            <div class="summary-item">Promedio por entrada: $${parseFloat(data.summary.promedio_por_entrada).toFixed(2)}</div>
                        `;
                    }
                }

                actualizarControlesPaginacionReservas();
            } else {
                alert('Error al cargar el reporte de reservas.');
            }
        } catch (error) {
            console.error("Error al cargar reporte:", error);
            document.getElementById('tablaReservas').innerHTML = '<tr><td colspan="6" class="mensaje-error">Error al cargar reporte</td></tr>';
        }
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

    // Cargar el reporte de reservas al iniciar
    cargarReporteReservas();
    cargarPeliculas(currentPagePeliculas);
});