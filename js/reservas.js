// Variables globales para el usuario autenticado
const usuario = JSON.parse(sessionStorage.getItem('usuario') || 'null');
const tipoUsuario = sessionStorage.getItem('tipoUsuario');

// Variables globales para la reserva
let idFuncion = null;
let precio = 0;

document.addEventListener('DOMContentLoaded', () => {
  // Verificar autenticación con el sistema unificado
  if (!usuario || tipoUsuario !== 'cliente') {
      // Mostrar advertencia y redirigir al login
      alert('Debes iniciar sesión como cliente para realizar una reserva.');
      window.location.href = 'login_unificado.html';
      return;
  }

  // Obtener ID de función desde URL o localStorage
  const urlParams = new URLSearchParams(window.location.search);
  idFuncion = urlParams.get('id_funcion') || localStorage.getItem('id_funcion');
  
  if (!idFuncion || idFuncion === 'null') {
    alert('No se ha seleccionado una función válida.');
    window.location.href = 'funciones.html';
    return;
  }
  
  // Guardar en localStorage para uso posterior
  localStorage.setItem('id_funcion', idFuncion);

  async function cargarAsientosOcupados() {
    try {
      const res = await fetch(`../php/entradas_ocupadas.php?id_funcion=${idFuncion}`);
      const ocupados = await res.json();

      // Validar que la respuesta sea un array
      if (Array.isArray(ocupados)) {
        ocupados.forEach(asiento => {
          const asientoElement = document.getElementById(asiento);
          if (asientoElement) {
            asientoElement.disabled = true;
            asientoElement.classList.add('ocupado'); // Agregar clase para estilo visual
          }
        });
      } else {
        console.warn('La respuesta de asientos ocupados no es un array:', ocupados);
      }
    } catch (error) {
      console.error('Error al cargar asientos ocupados:', error);
    }
  }

  // Cargar datos iniciales
  cargarAsientosOcupados();
  cargarPrecio();

  // Aquí va la lógica de reservas (mapa de asientos, selección, etc.)
  console.log(`Cliente logueado: ${usuario.nombre}`);
});

const params = new URLSearchParams(window.location.search);
// Usar la variable global idFuncion que ya fue inicializada
const seats = document.querySelectorAll('.seat:not(.occupied)');
const selectedList = document.getElementById('selected-list');
const totalDiv = document.getElementById('total');
const confirmarBtn = document.getElementById('confirmar-btn');

// Obtener precio de la función
async function cargarPrecio() {
  if (!idFuncion) {
    console.error('No hay ID de función para cargar precio');
    return;
  }
  
  try {
    console.log('Cargando precio para función ID:', idFuncion);
    const res = await fetch(`../php/funciones.php?id_funcion=${idFuncion}`);
    const funcion = await res.json();
    console.log('Respuesta del servidor:', funcion);
    
    if (funcion && funcion.precio !== undefined) {
      precio = parseFloat(funcion.precio) || 0;
      console.log('Precio cargado:', precio);
    } else {
      console.error('No se pudo obtener el precio de la función');
      precio = 0;
    }
    
    updateSelectedSeats();
  } catch (error) {
    console.error('Error al cargar precio:', error);
    precio = 0;
    updateSelectedSeats();
  }
}

seats.forEach(seat => {
  seat.addEventListener('click', () => {
    if (!seat.classList.contains('occupied')) {
      seat.classList.toggle('selected');
      updateSelectedSeats();
    }
  });
});

function updateSelectedSeats() {
  const selectedSeats = document.querySelectorAll('.seat.selected');
  const seatNumbers = [...selectedSeats].map(seat => seat.getAttribute('data-seat'));
  
  // Actualizar lista de asientos seleccionados
  if (selectedList) {
    selectedList.textContent = seatNumbers.length > 0 ? seatNumbers.join(', ') : 'ninguno';
  }
  
  // Calcular y mostrar total
  const total = seatNumbers.length * precio;
  console.log(`Asientos seleccionados: ${seatNumbers.length}, Precio unitario: ${precio}, Total: ${total}`);
  
  if (totalDiv) {
    totalDiv.textContent = `Total: $${total.toFixed(2)}`;
  }
}

// Confirmar reserva (envía al backend)
confirmarBtn.addEventListener('click', async () => {
  // Prevenir múltiples clics
  if (confirmarBtn.disabled) {
    return;
  }
  
  const selectedSeats = document.querySelectorAll('.seat.selected');
  const seatNumbers = [...selectedSeats].map(seat => seat.getAttribute('data-seat'));
  if (seatNumbers.length === 0) {
    alert('Selecciona al menos un asiento.');
    return;
  }
  const total = seatNumbers.length * precio;

  // Obtener la ID del cliente logueado desde el sistema unificado
  const id_cliente = usuario.id;

  if (!id_cliente) {
    alert('Error: No se pudo identificar al cliente. Por favor, inicia sesión nuevamente.');
    window.location.href = 'login_unificado.html';
    return;
  }

  console.log('Cliente identificado:', { id: id_cliente, nombre: usuario.nombre });

  // Deshabilitar botón para prevenir doble clic
  confirmarBtn.disabled = true;
  confirmarBtn.textContent = 'Procesando...';

  try {
    const res = await fetch('../php/reservas.php', {
      method: 'POST',
      body: JSON.stringify({
        id_funcion: idFuncion,
        asientos: seatNumbers,
        cantidad: seatNumbers.length,
        total_pagado: total,
        id_cliente
      }),
      headers: { 'Content-Type': 'application/json' }
    });
    const result = await res.json();
    
    if (result.success) {
      alert('¡Reserva exitosa!');
      window.location.href = 'index.html';
    } else {
      alert(result.error || 'Error al reservar.');
    }
  } catch (error) {
    console.error('Error al procesar reserva:', error);
    alert('Error de conexión. Intenta nuevamente.');
  } finally {
    // Rehabilitar botón
    confirmarBtn.disabled = false;
    confirmarBtn.textContent = 'Confirmar reserva';
  }
});

async function marcarAsientosOcupados() {
  try {
    const res = await fetch(`../php/entradas_ocupadas.php?id_funcion=${idFuncion}`);
    const ocupados = await res.json(); // Array de códigos de asiento ocupados
    
    if (Array.isArray(ocupados)) {
      ocupados.forEach(codigo => {
        const seat = document.querySelector(`.seat[data-seat="${codigo}"]`);
        if (seat) seat.classList.add('occupied');
      });
    }
  } catch (error) {
    console.error('Error al marcar asientos ocupados:', error);
  }
}
