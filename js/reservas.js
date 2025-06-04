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
  // Si tienes login, obtén el id_cliente, si no, envía null
  const id_cliente = null; // O reemplaza por el id real si tienes login

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
