document.addEventListener('DOMContentLoaded', () => {
  const cliente = JSON.parse(localStorage.getItem('cliente'));

  if (!cliente) {
      // Mostrar advertencia y redirigir al login
      document.getElementById('reservasWarning').style.display = 'block';
      alert('Debes iniciar sesión para realizar una reserva.');
      window.location.href = 'login_cliente.html';
      return;
  }

  const idFuncion = localStorage.getItem('id_funcion'); // Obtener ID de la función seleccionada

  async function cargarAsientosOcupados() {
    const res = await fetch(`../php/entradas_ocupadas.php?id_funcion=${idFuncion}`);
    const ocupados = await res.json();

    ocupados.forEach(asiento => {
      const asientoElement = document.getElementById(asiento);
      if (asientoElement) {
        asientoElement.disabled = true;
        asientoElement.classList.add('ocupado'); // Agregar clase para estilo visual
      }
    });
  }

  cargarAsientosOcupados();

  // Aquí va la lógica de reservas (mapa de asientos, selección, etc.)
  console.log(`Cliente logueado: ${cliente.nombre}`);
});

const params = new URLSearchParams(window.location.search);
const id_funcion = params.get('id_funcion');
const seats = document.querySelectorAll('.seat:not(.occupied)');
const selectedList = document.getElementById('selected-list');
const totalDiv = document.getElementById('total');
const confirmarBtn = document.getElementById('confirmar-btn');

let precio = 0;

// Obtener precio de la función
async function cargarPrecio() {
  if (!id_funcion) return;
  const res = await fetch(`../php/funciones.php?id_funcion=${id_funcion}`);
  const funcion = await res.json();
  precio = parseFloat(funcion.precio) || 0;
  updateSelectedSeats();
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
  selectedList.textContent = seatNumbers.length > 0 ? seatNumbers.join(', ') : 'ninguno';
  const total = seatNumbers.length * precio;
  totalDiv.textContent = `Total: $${total.toFixed(2)}`;
}

// Confirmar reserva (envía al backend)
confirmarBtn.addEventListener('click', async () => {
  const selectedSeats = document.querySelectorAll('.seat.selected');
  const seatNumbers = [...selectedSeats].map(seat => seat.getAttribute('data-seat'));
  if (seatNumbers.length === 0) {
    alert('Selecciona al menos un asiento.');
    return;
  }
  const total = seatNumbers.length * precio;

  // Obtener la ID del cliente logueado desde localStorage
  const cliente = JSON.parse(localStorage.getItem('cliente'));
  const id_cliente = cliente ? cliente.id : null;

  const res = await fetch('../php/reservas.php', {
    method: 'POST',
    body: JSON.stringify({
      id_funcion,
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
});

async function marcarAsientosOcupados() {
  const res = await fetch(`../php/entradas_ocupadas.php?id_funcion=${id_funcion}`);
  const ocupados = await res.json(); // Array de códigos de asiento ocupados
  ocupados.forEach(codigo => {
    const seat = document.querySelector(`.seat[data-seat="${codigo}"]`);
    if (seat) seat.classList.add('occupied');
  });
}
marcarAsientosOcupados();

cargarPrecio();
