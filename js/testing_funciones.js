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
    cargarFunciones(id);
  } else {
    alert('No se seleccionó ninguna película.');
    window.location.href = 'test_peliculas.html';
  }

  // Flatpickr solo para hora
  flatpickr("#hora", {
    enableTime: true,
    noCalendar: true,
    dateFormat: "H:i",
    time_24hr: true,
    minuteIncrement: 5,
    allowInput: false // Solo selección visual, no escritura
  });

  document.getElementById('formAgregarFuncion').addEventListener('submit', async function(e) {
    e.preventDefault();
    const fecha = document.getElementById('fecha').value;
    const hora = document.getElementById('hora').value;
    const precio = document.getElementById('precio').value;

    // Envía la función al backend
    const res = await fetch('../php/funciones.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        id_pelicula: id,
        fecha,
        hora,
        precio
      })
    });
    const data = await res.json();
    if (data.success) {
      document.getElementById('resultadoTesting').innerHTML = `<span style="color:limegreen;">✔ Función agregada correctamente</span>`;
      this.reset();
      cargarFunciones(id);
    } else {
      document.getElementById('resultadoTesting').innerHTML = `<span style="color:red;">Error al agregar función</span>`;
    }
  });
});

// Función para cargar y mostrar funciones de la película
async function cargarFunciones(id_pelicula) {
  const res = await fetch(`../php/funciones.php?id_pelicula=${id_pelicula}`);
  const funciones = await res.json();
  let html = '<h4>Funciones programadas:</h4>';
  if (funciones.length === 0) {
    html += '<p>No hay funciones asignadas.</p>';
  } else {
    html += `<ul style="list-style:none;padding:0;">`;
    funciones.forEach(f => {
      html += `<li style="margin-bottom:8px;">
        <i class="fa-solid fa-calendar-days"></i> <b>${f.fecha}</b> 
        <i class="fa-solid fa-clock"></i> <b>${f.hora_inicio}</b> 
        <i class="fa-solid fa-dollar-sign"></i> <b>$${f.precio}</b>
      </li>`;
    });
    html += `</ul>`;
  }
  document.getElementById('resultadoTesting').innerHTML = html;
}