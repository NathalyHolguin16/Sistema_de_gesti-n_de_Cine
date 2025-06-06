// Detecta si estamos en el panel de administración
const esAdmin = window.location.pathname.includes('test_peliculas.html');

const peliculasGrid = document.getElementById('peliculasGrid');
async function cargarPeliculas() {
  const res = await fetch('../php/peliculas.php');
  const peliculas = await res.json();
  peliculasGrid.innerHTML = "";
  peliculas.forEach(p => {
    const card = document.createElement('div');
    card.className = 'pelicula-card';
    const generoClase = "genero " + (p.genero || '').toLowerCase().replace(/ /g, "-");
    card.innerHTML = `
      <div class="pelicula-info">
        <img src="${p.imagen_url || '../resources/default.jpg'}" alt="${p.titulo}" class="pelicula-poster" />
        <h3>${p.titulo}</h3>
        <p class="${generoClase}">${p.genero}</p>
        <p>Duración: ${p.duracion_minutos} min</p>
        <p>Clasificación: ${p.clasificacion}</p>
        <button class="ver-funciones-btn" onclick="verFunciones(${p.id_pelicula})">Ver funciones</button>
        ${esAdmin ? `
          <button onclick="editarPelicula(${p.id_pelicula})">Editar</button>
          <button onclick="eliminarPelicula(${p.id_pelicula})">Eliminar</button>
        ` : ''}
      </div>
    `;
    peliculasGrid.appendChild(card);
  });
}

// Eliminar película (solo disponible en admin)
async function eliminarPelicula(id_pelicula) {
  if (!esAdmin) return;
  await fetch('../php/peliculas.php', {
    method: 'DELETE',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `id_pelicula=${id_pelicula}`
  });
  cargarPeliculas();
}

// Llama a cargarPeliculas al iniciar
cargarPeliculas();

// Manejo del formulario para agregar o editar (solo en admin)
const form = document.getElementById('formAgregarPelicula');
if (form) {
  form.onsubmit = async function(e) {
    e.preventDefault();
    const formData = new FormData(form);

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
    document.getElementById('btnGuardar').textContent = "Agregar Película";
    document.getElementById('btnCancelar').style.display = "none";
    cargarPeliculas();
  };
}

function agregarFuncion(idPelicula, imagen, titulo) {
  const params = new URLSearchParams({
    id: idPelicula,
    imagen: imagen,
    titulo: titulo
  });
  window.location.href = `testing-funciones.html?${params.toString()}`;
}

function verFunciones(id_pelicula) {
  window.location.href = `funciones.html?id_pelicula=${id_pelicula}`;
}

window.agregarFuncion = agregarFuncion;
window.verFunciones = verFunciones;

