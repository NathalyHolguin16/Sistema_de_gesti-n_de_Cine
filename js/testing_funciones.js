let currentPageFunciones = 1;
const limitFunciones = 10;
let paginationInfoFunciones = {};

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

    // Ensure id_empleado is sent in function actions
    const empleado = JSON.parse(localStorage.getItem('empleado'));
    if (!empleado || !empleado.id) {
      alert('Debes iniciar sesión como empleado para realizar esta acción.');
      return;
    }
    // Envía la función al backend
    const res = await fetch('../php/funciones.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        id_pelicula: id,
        fecha,
        hora,
        precio,
        id_empleado: empleado.id
      })
    });

    const result = await res.json();
    if (result.success) {
      alert('Función agregada correctamente.');
      document.getElementById('formAgregarFuncion').reset();
      cargarFunciones(id);
    } else {
      alert(result.error || 'Error al agregar función.');
    }
  });

  // Event listeners para paginación
  setupPaginationControls();
});

// Función para cargar y mostrar funciones de la película
async function cargarFunciones(id_pelicula, page = 1) {
  try {
    const res = await fetch(`../php/funciones.php?id_pelicula=${id_pelicula}&page=${page}&limit=${limitFunciones}`);
    const response = await res.json();

    let html = '<h4>Funciones programadas:</h4>';
    
    if (response.success && response.data) {
      paginationInfoFunciones = response.pagination;
      currentPageFunciones = paginationInfoFunciones.current_page;

      if (response.data.length === 0) {
        html += '<p>No hay funciones asignadas.</p>';
      } else {
        html += `<ul style="list-style:none;padding:0;">`;
        response.data.forEach(f => {
          html += `<li style="margin-bottom:8px;">
            <i class="fa-solid fa-calendar-days"></i> <b>${f.fecha}</b> 
            <i class="fa-solid fa-clock"></i> <b>${f.hora_inicio}</b> 
            <i class="fa-solid fa-dollar-sign"></i> <b>$${f.precio}</b>
            <i class="fa-solid fa-door-open"></i> <b>Sala ${f.sala_nombre || f.id_sala}</b>
          </li>`;
        });
        html += `</ul>`;

        // Agregar controles de paginación si hay más de una página
        if (paginationInfoFunciones.total_pages > 1) {
          html += `
            <div class="pagination-controls">
              <button id="prevPageFunciones" ${!paginationInfoFunciones.has_prev_page ? 'disabled' : ''}>
                <i class="fa-solid fa-chevron-left"></i> Anterior
              </button>
              <span id="pageInfoFunciones">
                Página ${paginationInfoFunciones.current_page} de ${paginationInfoFunciones.total_pages}
              </span>
              <button id="nextPageFunciones" ${!paginationInfoFunciones.has_next_page ? 'disabled' : ''}>
                Siguiente <i class="fa-solid fa-chevron-right"></i>
              </button>
            </div>
          `;
        }
      }
    } else {
      html += '<p>Error al cargar funciones.</p>';
    }

    document.getElementById('resultadoTesting').innerHTML = html;

    // Reconfigurar event listeners para los nuevos botones
    setupPaginationControls();

  } catch (error) {
    console.error("Error al cargar funciones:", error);
    document.getElementById('resultadoTesting').innerHTML = '<h4>Funciones programadas:</h4><p>Error al cargar funciones.</p>';
  }
}

// Configurar controles de paginación
function setupPaginationControls() {
  const params = new URLSearchParams(window.location.search);
  const id = params.get('id');

  const prevBtn = document.getElementById('prevPageFunciones');
  const nextBtn = document.getElementById('nextPageFunciones');

  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      if (paginationInfoFunciones.has_prev_page && currentPageFunciones > 1) {
        cargarFunciones(id, currentPageFunciones - 1);
      }
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      if (paginationInfoFunciones.has_next_page) {
        cargarFunciones(id, currentPageFunciones + 1);
      }
    });
  }
}