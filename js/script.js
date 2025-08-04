// Detecta si estamos en el panel de administración
const esAdmin = window.location.pathname.includes('test_peliculas.html');

let currentPagePeliculas = 1;
const limitPeliculas = 8;
let paginationInfoPeliculas = {};

async function cargarPeliculas(page = 1) {
  try {
    const res = await fetch(`../php/peliculas.php?page=${page}&limit=${limitPeliculas}`);
    const response = await res.json();
    const grid = document.getElementById('peliculasGrid');
    grid.innerHTML = '';

    if (response.success) {
      const peliculas = response.data;
      paginationInfoPeliculas = response.pagination;
      currentPagePeliculas = paginationInfoPeliculas.current_page;

      if (!peliculas.length) {
        grid.innerHTML = '<div class="mensaje-info">No hay películas registradas.</div>';
        actualizarControlesPaginacion();
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

      actualizarControlesPaginacion();
    } else {
      console.error("Error al cargar películas:", response.error);
      grid.innerHTML = '<div class="mensaje-error">Error al cargar películas</div>';
    }
  } catch (error) {
    console.error("Error en la petición:", error);
    document.getElementById('peliculasGrid').innerHTML = '<div class="mensaje-error">Error de conexión</div>';
  }
}

// Función para actualizar los controles de paginación
function actualizarControlesPaginacion() {
  const prevBtn = document.getElementById('prevPagePeliculas');
  const nextBtn = document.getElementById('nextPagePeliculas');
  const pageInfo = document.getElementById('pageInfoPeliculas');

  if (prevBtn) {
    prevBtn.disabled = !paginationInfoPeliculas.has_prev_page;
    prevBtn.style.opacity = paginationInfoPeliculas.has_prev_page ? '1' : '0.5';
  }

  if (nextBtn) {
    nextBtn.disabled = !paginationInfoPeliculas.has_next_page;
    nextBtn.style.opacity = paginationInfoPeliculas.has_next_page ? '1' : '0.5';
  }

  if (pageInfo) {
    pageInfo.textContent = `Página ${paginationInfoPeliculas.current_page || 1} de ${paginationInfoPeliculas.total_pages || 1} (${paginationInfoPeliculas.total_items || 0} películas)`;
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
  // Usar el nuevo sistema unificado de autenticación
  const usuario = JSON.parse(sessionStorage.getItem('usuario') || 'null');
  const tipoUsuario = sessionStorage.getItem('tipoUsuario');

  if (usuario && tipoUsuario === 'cliente') {
    // Mostrar mensaje de bienvenida para clientes
    document.getElementById('userOptions').style.display = 'none';
    document.getElementById('welcomeMessage').style.display = 'inline';
    document.getElementById('clienteNombre').textContent = usuario.nombre;
    document.getElementById('adminOptions').style.display = 'none';
  } else if (usuario && tipoUsuario === 'empleado') {
    // Mostrar opciones de administración para empleados
    document.getElementById('userOptions').style.display = 'none';
    document.getElementById('welcomeMessage').style.display = 'inline';
    document.getElementById('clienteNombre').textContent = usuario.nombre;
    if (usuario.rol === 'Administrador' || usuario.rol === 'Empleado') {
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
document.getElementById('logoutBtn').addEventListener('click', async () => {
  try {
    // Obtener datos de la sesión antes de limpiarla
    const usuario = JSON.parse(sessionStorage.getItem('usuario') || 'null');
    const tipoUsuario = sessionStorage.getItem('tipoUsuario');
    
    if (usuario && tipoUsuario) {
      // Enviar solicitud de logout al servidor para registrar en bitácora
      const response = await fetch('../php/login_unificado.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          action: 'logout',
          usuario: usuario,
          tipo: tipoUsuario
        })
      });
      
      const result = await response.json();
      if (result.success) {
        console.log('Logout registrado en bitácora');
      } else {
        console.warn('Error al registrar logout:', result.error);
      }
    }
  } catch (error) {
    console.error('Error en logout:', error);
  } finally {
    // Limpiar la nueva estructura de datos del sistema unificado
    sessionStorage.removeItem('usuario');
    sessionStorage.removeItem('tipoUsuario');
    sessionStorage.removeItem('clientInfo');
    
    verificarLogin();
    alert('Has cerrado sesión.');
    window.location.href = 'index.html'; // Redirige al inicio
  }
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

// Event listeners para paginación
const prevPagePeliculas = document.getElementById('prevPagePeliculas');
const nextPagePeliculas = document.getElementById('nextPagePeliculas');

if (prevPagePeliculas) {
  prevPagePeliculas.addEventListener('click', () => {
    if (paginationInfoPeliculas.has_prev_page && currentPagePeliculas > 1) {
      cargarPeliculas(currentPagePeliculas - 1);
    }
  });
}

if (nextPagePeliculas) {
  nextPagePeliculas.addEventListener('click', () => {
    if (paginationInfoPeliculas.has_next_page) {
      cargarPeliculas(currentPagePeliculas + 1);
    }
  });
}

// Función para ir a una página específica
function irAPagina(page) {
  if (page >= 1 && page <= (paginationInfoPeliculas.total_pages || 1)) {
    cargarPeliculas(page);
  }
}

// Ejecutar la verificación al cargar la página
document.addEventListener('DOMContentLoaded', verificarLogin);

window.agregarFuncion = agregarFuncion;
window.verFunciones = verFunciones;

