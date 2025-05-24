const peliculas = [
  {
    id: 1,
    titulo: "Inception",
    genero: "Ciencia ficción",
    duracion: 148,
    sinopsis: "Un ladrón que roba secretos a través de los sueños",
    poster: "Shawshank.jpg"
  },
  {
    id: 2,
    titulo: "El Gran Showman",
    genero: "Musical",
    duracion: 105,
    sinopsis: "Historia de un visionario que crea un espectáculo sin igual",
    poster: "El Gran Showman.jpg"
  }
];

const funciones = [
  { id: 1, peliculaId: 1, fechaHora: "2025-06-01 18:00" },
  { id: 2, peliculaId: 1, fechaHora: "2025-06-01 21:00" },
  { id: 3, peliculaId: 2, fechaHora: "2025-06-02 19:00" },
  { id: 4, peliculaId: 2, fechaHora: "2025-06-02 22:00" }
];

const peliculasGrid = document.getElementById('peliculasGrid');
function cargarPeliculas() {
  peliculasGrid.innerHTML = "";
  peliculas.forEach(p => {
    const card = document.createElement('div');
    card.className = 'pelicula-card';
    card.innerHTML = `
      <img src="${p.poster}" alt="${p.titulo}" class="pelicula-poster" />
      <div class="pelicula-info">
        <h3>${p.titulo}</h3>
        <p class="genero ${p.genero.toLowerCase().replace(/\s/g, '-')}">${p.genero}</p>
        <p>${p.sinopsis}</p>
        <button onclick="verFunciones(${p.id})">Ver funciones</button>
      </div>
    `;
    peliculasGrid.appendChild(card);
  });
}

const funcionesSection = document.getElementById('funciones');
const carteleraSection = document.getElementById('cartelera');
const peliculaTituloSpan = document.getElementById('peliculaTitulo');
const funcionListaDiv = document.getElementById('funcionLista');

function verFunciones(peliculaId) {
  const pelicula = peliculas.find(p => p.id === peliculaId);
  peliculaTituloSpan.textContent = pelicula.titulo;

  const funcs = funciones.filter(f => f.peliculaId === peliculaId);
  funcionListaDiv.innerHTML = "";
  funcs.forEach(f => {
    const btn = document.createElement('button');
    btn.textContent = new Date(f.fechaHora).toLocaleString();
    btn.onclick = () => {
      showSection('reservas');
      seleccionarPeliculaFuncion(peliculaId, f.id);
    };
    funcionListaDiv.appendChild(btn);
  });

  showSection('funciones');
}

function showSection(seccion) {
  document.getElementById('cartelera').style.display = 'none';
  document.getElementById('funciones').style.display = 'none';
  document.getElementById('reservas').style.display = 'none';

  document.getElementById(seccion).style.display = 'block';
}

const peliculaSelect = document.getElementById('peliculaSelect');
const funcionSelect = document.getElementById('funcionSelect');

function llenarPeliculasSelect() {
  peliculaSelect.innerHTML = "";
  peliculas.forEach(p => {
    const option = document.createElement('option');
    option.value = p.id;
    option.textContent = p.titulo;
    peliculaSelect.appendChild(option);
  });
}

function llenarFuncionesSelect(peliculaId) {
  funcionSelect.innerHTML = "";
  const funcs = funciones.filter(f => f.peliculaId == peliculaId);
  funcs.forEach(f => {
    const option = document.createElement('option');
    option.value = f.id;
    option.textContent = new Date(f.fechaHora).toLocaleString();
    funcionSelect.appendChild(option);
  });
}

function seleccionarPeliculaFuncion(peliculaId, funcionId) {
  llenarPeliculasSelect();
  peliculaSelect.value = peliculaId;
  llenarFuncionesSelect(peliculaId);
  funcionSelect.value = funcionId;
}

peliculaSelect.addEventListener('change', () => {
  llenarFuncionesSelect(peliculaSelect.value);
});

const reservaForm = document.getElementById('reservaForm');
reservaForm.addEventListener('submit', (e) => {
  e.preventDefault();
  alert(`Reserva confirmada para ${document.getElementById('nombreCliente').value} en la película "${peliculas.find(p => p.id == peliculaSelect.value).titulo}" en la función seleccionada.`);
  reservaForm.reset();
  showSection('cartelera');
});

// Carga inicial
cargarPeliculas();
llenarPeliculasSelect();
showSection('cartelera');

