// JavaScript para el login unificado
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    const errorMessage = document.getElementById('errorMessage');
    const successMessage = document.getElementById('successMessage');
    const clientInfo = document.getElementById('clientInfo');

    // Elementos para mostrar información del cliente
    const userIP = document.getElementById('userIP');
    const userBrowser = document.getElementById('userBrowser');
    const userOS = document.getElementById('userOS');
    const userDevice = document.getElementById('userDevice');

    // Función para mostrar/ocultar mensajes
    function showMessage(element, message, isError = false) {
        element.textContent = message;
        element.style.display = 'block';
        
        // Ocultar el mensaje opuesto
        if (isError) {
            successMessage.style.display = 'none';
        } else {
            errorMessage.style.display = 'none';
        }

        // Auto-ocultar después de 5 segundos
        setTimeout(() => {
            element.style.display = 'none';
        }, 5000);
    }

    // Función para actualizar información del cliente en la UI
    function updateClientInfo(clientData, userAgentData) {
        userIP.textContent = clientData.ip || 'No disponible';
        
        if (userAgentData) {
            userBrowser.textContent = `${userAgentData.navegador} ${userAgentData.version}`;
            userOS.textContent = userAgentData.sistema_operativo;
            userDevice.textContent = userAgentData.dispositivo;
        }
        
        clientInfo.style.display = 'block';
    }

    // Función para manejar el estado de carga del botón
    function setLoadingState(isLoading) {
        loginBtn.disabled = isLoading;
        
        if (isLoading) {
            loginBtn.classList.add('loading');
            document.querySelector('.btn-text').style.opacity = '0';
            document.querySelector('.loading-spinner').style.display = 'inline-block';
        } else {
            loginBtn.classList.remove('loading');
            document.querySelector('.btn-text').style.opacity = '1';
            document.querySelector('.loading-spinner').style.display = 'none';
        }
    }

    // Manejar envío del formulario
    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Obtener datos del formulario
        const credencial = document.getElementById('credencial').value.trim();
        const contrasena = document.getElementById('contrasena').value;

        // Validaciones básicas
        if (!credencial || !contrasena) {
            showMessage(errorMessage, 'Por favor, completa todos los campos.', true);
            return;
        }

        if (contrasena.length < 6) {
            showMessage(errorMessage, 'La contraseña debe tener al menos 6 caracteres.', true);
            return;
        }

        // Configurar estado de carga
        setLoadingState(true);
        errorMessage.style.display = 'none';
        successMessage.style.display = 'none';

        try {
            // Realizar petición al servidor
            const response = await fetch('../php/login_unificado.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    credencial: credencial,
                    contrasena: contrasena
                })
            });

            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                // Login exitoso
                const tipoUsuario = data.tipo === 'cliente' ? 'Cliente' : 'Empleado';
                const mensaje = `${data.mensaje} (${tipoUsuario})`;
                
                showMessage(successMessage, mensaje, false);

                // Actualizar información del cliente en la UI
                if (data.client_info && data.user_agent_info) {
                    updateClientInfo(data.client_info, data.user_agent_info);
                }

                // Mostrar alerta de actividad sospechosa si es necesario
                if (data.actividad_sospechosa) {
                    setTimeout(() => {
                        alert('⚠️ ALERTA DE SEGURIDAD\n\nSe ha detectado actividad sospechosa en tu cuenta.\n\nSi no reconoces este acceso, contacta al administrador inmediatamente.');
                    }, 1000);
                }

                // Guardar información del usuario en sessionStorage
                sessionStorage.setItem('usuario', JSON.stringify(data.usuario));
                sessionStorage.setItem('tipoUsuario', data.tipo);
                sessionStorage.setItem('clientInfo', JSON.stringify(data.client_info));

                // Mostrar información adicional por 2 segundos antes de redirigir
                setTimeout(() => {
                    // Redirigir según el tipo de usuario
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        // Fallback por si no viene la URL de redirección
                        if (data.tipo === 'cliente') {
                            window.location.href = 'funciones.html';
                        } else {
                            window.location.href = 'administracion.html';
                        }
                    }
                }, 2000);

            } else {
                // Error de login
                showMessage(errorMessage, data.error || 'Error al iniciar sesión.', true);
                
                // Limpiar campo de contraseña por seguridad
                document.getElementById('contrasena').value = '';
                document.getElementById('contrasena').focus();
            }

        } catch (error) {
            console.error('Error en login:', error);
            showMessage(errorMessage, 'Error de conexión. Por favor, inténtalo de nuevo.', true);
        } finally {
            setLoadingState(false);
        }
    });

    // Mejorar UX: Enter en los campos
    document.getElementById('credencial').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            document.getElementById('contrasena').focus();
        }
    });

    document.getElementById('contrasena').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            loginForm.dispatchEvent(new Event('submit'));
        }
    });

    // Auto-focus en el primer campo
    document.getElementById('credencial').focus();

    // Función para detectar el tipo de credencial mientras el usuario escribe
    document.getElementById('credencial').addEventListener('input', function(e) {
        const value = e.target.value;
        const isEmail = value.includes('@');
        const helpText = document.querySelector('.help-text');
        
        if (value.length > 0) {
            if (isEmail) {
                helpText.innerHTML = '✉️ Detectado: Correo electrónico<br>• Se buscará en clientes y empleados';
                helpText.style.color = '#059669';
            } else {
                helpText.innerHTML = '👤 Detectado: Nombre de usuario<br>• Se buscará en empleados';
                helpText.style.color = '#b45309';
            }
        } else {
            helpText.innerHTML = '• Clientes: Usa tu correo electrónico<br>• Empleados: Usa tu usuario o correo';
            helpText.style.color = '#888';
        }
    });

    // Función para manejar errores de red
    window.addEventListener('offline', function() {
        showMessage(errorMessage, 'Sin conexión a internet. Verifica tu conexión.', true);
    });

    window.addEventListener('online', function() {
        showMessage(successMessage, 'Conexión restaurada.', false);
    });

    console.log('🎬 Sistema de Login Unificado CineFacil iniciado');
    console.log('✅ Detecta automáticamente clientes y empleados');
    console.log('🔒 Incluye seguimiento de seguridad e IP');
});
