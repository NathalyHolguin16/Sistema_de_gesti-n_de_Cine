let currentPagePeliculas = 1;
const limitPeliculas = 8;

// Renderizado de tarjetas de películas
async function cargarPeliculas(page = 1) {
  const res = await fetch(`../php/peliculas.php?page=${page}&limit=${limitPeliculas}`);
  const response = await res.json();
  const grid = document.getElementById('peliculasGrid');
  grid.innerHTML = '';

  if (response.success) {
    const peliculas = response.data;
    if (!peliculas.length) {
      grid.innerHTML = '<div class="mensaje-info">No hay películas registradas.</div>';
      return;
    }
    peliculas.forEach(p => {
      const card = document.createElement('div');
      card.className = 'pelicula-card-admin';
      card.innerHTML = `
        <div class="pelicula-card-img">
          <img src="${p.imagen ? '../resources/' + p.imagen : 'https://placehold.co/180x270?text=Sin+Imagen'}" alt="${p.titulo}" />
        </div>
        <div class="pelicula-card-info">
          <h4 title="${p.titulo}">${p.titulo}</h4>
          <span class="pelicula-chip"><i class="fa-solid fa-clock"></i> ${p.duracion_minutos} min</span>
          <span class="pelicula-chip"><i class="fa-solid fa-user-shield"></i> ${p.clasificacion}</span>
          <span class="pelicula-chip"><i class="fa-solid fa-masks-theater"></i> ${p.genero}</span>
          <p class="pelicula-sinopsis">${p.sinopsis ? p.sinopsis : '<em>Sin sinopsis</em>'}</p>
          <div class="pelicula-card-actions">
            <button onclick="editarPelicula('${p.id_pelicula}')" title="Editar"><i class="fa-solid fa-pen"></i></button>
            <button onclick="eliminarPelicula('${p.id_pelicula}')" title="Eliminar" class="btn-eliminar"><i class="fa-solid fa-trash"></i></button>
            <button onclick="agregarFuncion('${p.id_pelicula}', '${p.imagen}', '${p.titulo}')" title="Agregar Función">
              <i class="fa-solid fa-calendar-plus"></i> Agregar Función
            </button>
          </div>
        </div>
      `;
      grid.appendChild(card);
    });
  } else {
    console.error("Error al cargar películas:", response.error);
  }
}

// Función para redirigir a testing-funciones.html
function agregarFuncion(idPelicula, imagen, titulo) {
  const params = new URLSearchParams({
    id: idPelicula,
    imagen: imagen,
    titulo: titulo
  });
  window.location.href = `testing-funciones.html?${params.toString()}`;
}

// Función para editar película
function editarPelicula(id) {
  fetch('../php/peliculas.php')
    .then(res => res.json())
    .then(response => {
      if (response.success && Array.isArray(response.data)) {
        const peliculas = response.data;
        const peli = peliculas.find(p => p.id_pelicula == id);
        if (peli) {
          document.getElementById('id_pelicula').value = peli.id_pelicula;
          document.getElementById('titulo').value = peli.titulo;
          document.getElementById('duracion_minutos').value = peli.duracion_minutos;
          document.getElementById('clasificacion').value = peli.clasificacion;
          document.getElementById('genero').value = peli.genero;
          document.getElementById('sinopsis').value = peli.sinopsis;
          document.getElementById('btnGuardar').innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar Cambios';
          document.getElementById('btnCancelar').style.display = "inline";
        }
      } else {
        console.error("Error al obtener películas o estructura inesperada:", response);
      }
    })
    .catch(error => console.error("Error al editar película:", error));
}

// Función para eliminar película
async function eliminarPelicula(id) {
  if (!confirm('¿Seguro que deseas eliminar esta película?')) return;
  await fetch('../php/peliculas.php', {
    method: 'DELETE',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `id_pelicula=${id}`
  });
  cargarPeliculas();
}

// Cancelar edición
document.getElementById('btnCancelar').onclick = function() {
  document.getElementById('formAgregarPelicula').reset();
  document.getElementById('id_pelicula').value = "";
  this.style.display = "none";
  document.getElementById('btnGuardar').innerHTML = '<i class="fa-solid fa-plus"></i> Agregar Película';
};

// Manejo del formulario para agregar o editar
document.getElementById('formAgregarPelicula').onsubmit = async function(e) {
  e.preventDefault();
  const form = this;
  const formData = new FormData(form);

  // Ensure id_empleado is sent in movie actions
  const empleado = JSON.parse(localStorage.getItem('empleado'));
  if (!empleado || !empleado.id) {
    alert('Debes iniciar sesión como empleado para realizar esta acción.');
    return;
  }
  formData.append('id_empleado', empleado.id);

  if (document.getElementById('id_pelicula').value) {
    formData.append('id_pelicula', document.getElementById('id_pelicula').value);
    formData.append('modo', 'editar');
  } else {
    formData.append('modo', 'agregar');
  }

  await fetch('../php/peliculas.php', {
    method: 'POST',
    body: formData
  });

  form.reset();
  document.getElementById('id_pelicula').value = "";
  document.getElementById('btnGuardar').innerHTML = '<i class="fa-solid fa-plus"></i> Agregar Película';
  document.getElementById('btnCancelar').style.display = "none";
  cargarPeliculas();
};

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

// Cargar películas al iniciar
cargarPeliculas(currentPagePeliculas);