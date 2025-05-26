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