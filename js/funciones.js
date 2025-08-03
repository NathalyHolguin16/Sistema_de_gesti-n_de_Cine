function getIdPeliculaFromUrl() {
  const params = new URLSearchParams(window.location.search);
  return params.get('id_pelicula');
}

let currentPageFuncionesPublic = 1;
const limitFuncionesPublic = 20;
let paginationInfoFuncionesPublic = {};

document.addEventListener('DOMContentLoaded', async () => {
  const id_pelicula = getIdPeliculaFromUrl();
  const posterImg = document.getElementById('peliculaPoster');

  if (!id_pelicula) {
    document.getElementById('funciones').innerHTML = "<div class='mensaje-error'>No se ha seleccionado ninguna película.</div>";
    return;
  }

  // Cargar datos de la película
  try {
    const resPeli = await fetch(`../php/peliculas.php?id_pelicula=${id_pelicula}`);
    const responsePeli = await resPeli.json();
    
    if (responsePeli.success && responsePeli.data) {
      const pelicula = responsePeli.data;
      
      document.getElementById('peliculaTitulo').textContent = pelicula.titulo;
      document.getElementById('sinopsis').textContent = pelicula.sinopsis;
      document.getElementById('duracion').textContent = pelicula.duracion_minutos + " min";
      document.getElementById('clasificacion').textContent = pelicula.clasificacion || '';
      document.getElementById('genero').textContent = pelicula.genero || '';
      posterImg.src = pelicula.imagen ? `../resources/${pelicula.imagen}` : "../resources/peli_683293c9484d4.jpg";
      posterImg.alt = pelicula.titulo;

      console.log('Película:', pelicula);
    }
  } catch (error) {
    console.error('Error al cargar película:', error);
  }

  // Cargar funciones de la película
  await cargarFuncionesPublic(id_pelicula);
});

async function cargarFuncionesPublic(id_pelicula, page = 1) {
  try {
    const resFunciones = await fetch(`../php/funciones.php?id_pelicula=${id_pelicula}&page=${page}&limit=${limitFuncionesPublic}`);
    const response = await resFunciones.json();
    
    console.log('Respuesta funciones:', response);

    const funcionListaDiv = document.getElementById('funcionLista');
    funcionListaDiv.innerHTML = "";

    if (response.success && response.data) {
      paginationInfoFuncionesPublic = response.pagination;
      currentPageFuncionesPublic = paginationInfoFuncionesPublic.current_page;

      const funciones = response.data;

      if (!funciones || funciones.length === 0) {
        funcionListaDiv.innerHTML = "<div class='mensaje-info'>No hay funciones programadas para esta película.</div>";
        return;
      }

      // Agrupar funciones por fecha
      const funcionesPorFecha = {};
      funciones.forEach(f => {
        if (!funcionesPorFecha[f.fecha]) funcionesPorFecha[f.fecha] = [];
        funcionesPorFecha[f.fecha].push(f);
      });

      function getNombreDia(fechaStr) {
        const dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        const fechaObj = new Date(fechaStr);
        return dias[fechaObj.getDay()];
      }

      Object.keys(funcionesPorFecha).forEach(fecha => {
        const fechaDiv = document.createElement('div');
        fechaDiv.className = 'funcion-fecha-bloque';
        const nombreDia = getNombreDia(fecha);
        fechaDiv.innerHTML = `<div class="funcion-fecha-titulo"><b>${nombreDia} ${fecha}</b></div>`;
        funcionesPorFecha[fecha].forEach(f => {
          const btn = document.createElement('button');
          btn.className = 'funcion-hora-btn';
          btn.textContent = f.hora_inicio;
          btn.onclick = () => window.location.href = `reservas.html?id_funcion=${f.id_funcion}`;
          fechaDiv.appendChild(btn);
        });
        funcionListaDiv.appendChild(fechaDiv);
      });

      // Agregar controles de paginación si hay más de una página
      if (paginationInfoFuncionesPublic.total_pages > 1) {
        const paginationDiv = document.createElement('div');
        paginationDiv.className = 'pagination-controls';
        paginationDiv.innerHTML = `
          <button id="prevPageFuncionesPublic" ${!paginationInfoFuncionesPublic.has_prev_page ? 'disabled' : ''}>
            <i class="fa-solid fa-chevron-left"></i> Anterior
          </button>
          <span id="pageInfoFuncionesPublic">
            Página ${paginationInfoFuncionesPublic.current_page} de ${paginationInfoFuncionesPublic.total_pages}
          </span>
          <button id="nextPageFuncionesPublic" ${!paginationInfoFuncionesPublic.has_next_page ? 'disabled' : ''}>
            Siguiente <i class="fa-solid fa-chevron-right"></i>
          </button>
        `;
        funcionListaDiv.appendChild(paginationDiv);

        // Configurar event listeners para paginación
        setupPaginationControlsPublic(id_pelicula);
      }

    } else {
      funcionListaDiv.innerHTML = "<div class='mensaje-error'>Error al cargar las funciones.</div>";
      console.error('Error en respuesta:', response);
    }
  } catch (e) {
    document.getElementById('funcionLista').innerHTML = "<div class='mensaje-error'>Error al cargar las funciones.</div>";
    console.error(e);
  }
}

function setupPaginationControlsPublic(id_pelicula) {
  const prevBtn = document.getElementById('prevPageFuncionesPublic');
  const nextBtn = document.getElementById('nextPageFuncionesPublic');

  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      if (paginationInfoFuncionesPublic.has_prev_page && currentPageFuncionesPublic > 1) {
        cargarFuncionesPublic(id_pelicula, currentPageFuncionesPublic - 1);
      }
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      if (paginationInfoFuncionesPublic.has_next_page) {
        cargarFuncionesPublic(id_pelicula, currentPageFuncionesPublic + 1);
      }
    });
  }
}