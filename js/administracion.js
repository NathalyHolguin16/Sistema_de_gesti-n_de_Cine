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

    let currentPagePeliculas = 1;
    const limitPeliculas = 10;

    async function cargarPeliculas(page) {
        const res = await fetch(`../php/peliculas.php?page=${page}&limit=${limitPeliculas}`);
        const data = await res.json();

        if (data.success) {
            const peliculasTabla = document.getElementById('peliculasTabla');
            peliculasTabla.innerHTML = '';
            data.data.forEach(pelicula => {
                peliculasTabla.innerHTML += `<tr><td>${pelicula.titulo}</td><td>${pelicula.genero}</td></tr>`;
            });
        }
    }

    // Event listeners para paginación
    const prevPagePeliculas = document.getElementById('prevPagePeliculas');
    const nextPagePeliculas = document.getElementById('nextPagePeliculas');

    prevPagePeliculas.addEventListener('click', () => {
        if (currentPagePeliculas > 1) {
            currentPagePeliculas--;
            cargarPeliculas(currentPagePeliculas);
        }
    });

    nextPagePeliculas.addEventListener('click', () => {
        currentPagePeliculas++;
        cargarPeliculas(currentPagePeliculas);
    });

    async function cargarReporteReservas() {
        const res = await fetch('../php/reportes.php');
        const data = await res.json();

        if (data.success) {
            const tablaReservas = document.getElementById('tablaReservas');
            tablaReservas.innerHTML = '';
            data.data.forEach(reserva => {
                tablaReservas.innerHTML += `
                    <tr>
                        <td>${reserva.cliente}</td>
                        <td>${reserva.pelicula}</td>
                        <td>${reserva.fecha}</td>
                        <td>${reserva.hora}</td>
                        <td>${reserva.asientos}</td>
                        <td>${reserva.total_pagado}</td>
                    </tr>`;
            });
        } else {
            alert('Error al cargar el reporte de reservas.');
        }
    }

    // Cargar el reporte de reservas al iniciar
    cargarReporteReservas();
    cargarPeliculas(currentPagePeliculas);
});