document.addEventListener('DOMContentLoaded', () => {
    const comidaForm = document.getElementById('comidaForm');
    const comidasGrid = document.getElementById('comidasGrid');
    const btnCancelar = document.getElementById('btnCancelar');
    
    // Cargar comidas al iniciar
    cargarComidas();
    
    // Event listeners
    comidaForm.addEventListener('submit', handleSubmit);
    btnCancelar.addEventListener('click', resetForm);
    
    // Funciones principales
    async function cargarComidas() {
        try {
            const response = await fetch('../php/comidas.php?action=obtener');
            const comidas = await response.json();
            
            comidasGrid.innerHTML = '';
            comidas.forEach(comida => {
                comidasGrid.appendChild(crearComidaCard(comida));
            });
        } catch (error) {
            console.error('Error al cargar comidas:', error);
            mostrarMensaje('Error al cargar las comidas', 'error');
        }
    }
    
    function crearComidaCard(comida) {
        const card = document.createElement('div');
        card.className = 'comida-card';
        card.innerHTML = `
            <div class="comida-imagen">
                <img src="../resources/${comida.imagen}" alt="${comida.nombre}">
            </div>
            <div class="comida-info">
                <h4>${comida.nombre}</h4>
                <div class="comida-precio">$${parseFloat(comida.precio).toFixed(2)}</div>
                <div class="comida-tipo">${comida.tipo}</div>
                <div class="comida-descripcion">${comida.descripcion}</div>
            </div>
            <div class="comida-acciones">
                <button class="btn-editar" onclick="editarComida(${comida.id_comida})">
                    <i class="fas fa-edit"></i> Editar
                </button>
                <button class="btn-eliminar" onclick="eliminarComida(${comida.id_comida})">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </div>
        `;
        return card;
    }
    
    async function handleSubmit(e) {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('nombre', document.getElementById('nombre').value);
        formData.append('descripcion', document.getElementById('descripcion').value);
        formData.append('precio', document.getElementById('precio').value);
        formData.append('tipo', document.getElementById('tipo').value);
        
        const imagenInput = document.getElementById('imagen');
        if (imagenInput.files.length > 0) {
            formData.append('imagen', imagenInput.files[0]);
        }
        
        const idComida = document.getElementById('idComida').value;
        formData.append('action', idComida ? 'actualizar' : 'agregar');
        if (idComida) formData.append('id_comida', idComida);
        
        try {
            const response = await fetch('../php/comidas.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                mostrarMensaje(result.message, 'success');
                resetForm();
                cargarComidas();
            } else {
                mostrarMensaje(result.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarMensaje('Error al procesar la solicitud', 'error');
        }
    }
    
    window.editarComida = async (id) => {
        try {
            const response = await fetch(`../php/comidas.php?action=obtener&id=${id}`);
            const comida = await response.json();
            
            document.getElementById('idComida').value = comida.id_comida;
            document.getElementById('nombre').value = comida.nombre;
            document.getElementById('descripcion').value = comida.descripcion;
            document.getElementById('precio').value = comida.precio;
            document.getElementById('tipo').value = comida.tipo;
            
            document.querySelector('.btn-guardar').innerHTML = '<i class="fas fa-save"></i> Actualizar';
        } catch (error) {
            console.error('Error al cargar comida:', error);
            mostrarMensaje('Error al cargar los datos de la comida', 'error');
        }
    };
    
    window.eliminarComida = async (id) => {
        if (!confirm('¿Estás seguro de que deseas eliminar esta comida?')) return;
        
        try {
            const response = await fetch('../php/comidas.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=eliminar&id_comida=${id}`
            });
            
            const result = await response.json();
            
            if (result.success) {
                mostrarMensaje(result.message, 'success');
                cargarComidas();
            } else {
                mostrarMensaje(result.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarMensaje('Error al eliminar la comida', 'error');
        }
    };
    
    function resetForm() {
        comidaForm.reset();
        document.getElementById('idComida').value = '';
        document.querySelector('.btn-guardar').innerHTML = '<i class="fas fa-save"></i> Guardar';
    }
    
    function mostrarMensaje(mensaje, tipo) {
        // Implementa tu propia lógica para mostrar mensajes
        alert(mensaje);
    }
});
