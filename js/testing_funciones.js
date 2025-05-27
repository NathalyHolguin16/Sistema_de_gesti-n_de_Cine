document.addEventListener('DOMContentLoaded', () => {
  const params = new URLSearchParams(window.location.search);
  const id = params.get('id');
  const imagen = params.get('imagen');
  const titulo = params.get('titulo');

  if (id && titulo) {
    // Renderiza la película seleccionada
    const card = document.getElementById('peliculaSeleccionadaCard');
    card.innerHTML = `
      <div class="pelicula-card-img">
        <img src="${imagen ? '../resources/' + imagen : 'https://placehold.co/180x270?text=Sin+Imagen'}" alt="${titulo}" />
      </div>
      <div class="pelicula-card-info">
        <h4 title="${titulo}">${titulo}</h4>
        <span class="pelicula-chip"><i class="fa-solid fa-id-badge"></i> ID: ${id}</span>
      </div>
    `;

    // Prellena el campo de película en el formulario
    document.getElementById('pelicula').value = titulo;
  } else {
    alert('No se seleccionó ninguna película.');
    window.location.href = 'test_peliculas.html'; // Redirige si no hay datos
  }
});

document.getElementById('formAgregarFuncion').addEventListener('submit', function(e) {
  e.preventDefault();
  const pelicula = document.getElementById('pelicula').value;
  const fechaHora = document.getElementById('fechaHora').value;
  const sala = document.getElementById('sala').value;
  const precio = document.getElementById('precio').value;
  document.getElementById('resultadoTesting').innerHTML = `
    <p><strong>Función agregada para:</strong></p>
    <ul>
      <li><i class="fa-solid fa-film"></i> <b>Película:</b> ${pelicula}</li>
      <li><i class="fa-solid fa-calendar-days"></i> <b>Fecha y Hora:</b> ${fechaHora}</li>
      <li><i class="fa-solid fa-door-open"></i> <b>Sala:</b> ${sala}</li>
      <li><i class="fa-solid fa-dollar-sign"></i> <b>Precio:</b> $${parseFloat(precio).toFixed(2)}</li>
    </ul>
  `;
  this.reset();
});