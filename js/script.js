// Detecta si estamos en el panel de administración
const esAdmin = window.location.pathname.includes('test_peliculas.html');

let currentPagePeliculas = 1;
const limitPeliculas = 8;

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
      grid.appendChild(card);
    });
  } else {
    console.error("Error al cargar películas:", response.error);
  }
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
cargarPeliculas(currentPagePeliculas);

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
    cargarPeliculas(currentPagePeliculas);
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

// Mostrar secciones dinámicamente
function showSection(sectionId) {
  const section = document.getElementById(sectionId);
  if (!section) {
    console.error(`La sección con id "${sectionId}" no existe.`);
    return;
  }
  document.querySelectorAll('main > section').forEach(section => {
    section.style.display = 'none';
  });
  section.style.display = 'block';
}

// Verificar si el cliente o empleado está logueado
function verificarLogin() {
  const cliente = JSON.parse(localStorage.getItem('cliente'));
  const empleado = JSON.parse(localStorage.getItem('empleado'));

  if (cliente) {
    // Mostrar mensaje de bienvenida para clientes
    document.getElementById('userOptions').style.display = 'none';
    document.getElementById('welcomeMessage').style.display = 'inline';
    document.getElementById('clienteNombre').textContent = cliente.nombre;
  } else if (empleado) {
    // Mostrar opciones de administración para empleados
    document.getElementById('userOptions').style.display = 'none';
    document.getElementById('welcomeMessage').style.display = 'inline';
    document.getElementById('clienteNombre').textContent = empleado.nombre;
    if (empleado.rol === 'Administrador' || empleado.rol === 'Empleado') {
      document.getElementById('adminOptions').style.display = 'inline';
    }
  } else {
    // Mostrar opciones de registro y login
    document.getElementById('userOptions').style.display = 'inline';
    document.getElementById('welcomeMessage').style.display = 'none';
    document.getElementById('adminOptions').style.display = 'none';
  }
}

// Cerrar sesión
document.getElementById('logoutBtn').addEventListener('click', () => {
  localStorage.removeItem('cliente');
  localStorage.removeItem('empleado');
  verificarLogin();
  alert('Has cerrado sesión.');
  window.location.href = 'index.html'; // Redirige al inicio
});

// Registro de cliente
document.getElementById('formRegistroCliente').addEventListener('submit', async (e) => {
  e.preventDefault();

  const nombre = document.getElementById('nombre').value;
  const correo = document.getElementById('correo').value;
  const telefono = document.getElementById('telefono').value;

  const res = await fetch('../php/registro_cliente.php', {
    method: 'POST',
    body: JSON.stringify({ nombre, correo, telefono }),
    headers: { 'Content-Type': 'application/json' }
  });

  const result = await res.json();
  if (result.success) {
    alert('¡Registro exitoso!');
    localStorage.setItem('cliente', JSON.stringify({ nombre, correo }));
    verificarLogin();
    showSection('cartelera');
  } else {
    alert(result.error || 'Error al registrarse.');
  }
});

// Login de cliente
document.getElementById('formLoginCliente').addEventListener('submit', async (e) => {
  e.preventDefault();

  const correo = document.getElementById('correoLogin').value;

  const res = await fetch('../php/login_cliente.php', {
    method: 'POST',
    body: JSON.stringify({ correo }),
    headers: { 'Content-Type': 'application/json' }
  });

  const result = await res.json();
  if (result.success) {
    alert('¡Login exitoso!');
    localStorage.setItem('cliente', JSON.stringify({ nombre: result.nombre, correo }));
    verificarLogin();
    showSection('cartelera');
  } else {
    alert(result.error || 'Error al iniciar sesión.');
  }
});

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

// Ejecutar la verificación al cargar la página
document.addEventListener('DOMContentLoaded', verificarLogin);

window.agregarFuncion = agregarFuncion;
window.verFunciones = verFunciones;

