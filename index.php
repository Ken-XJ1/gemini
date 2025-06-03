<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReciclApp - Transforma Residuos en Recompensas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <meta name="description" content="ReciclApp - Transforma tus residuos en recompensas. Únete a nuestra comunidad ecológica y comienza a reciclar de forma inteligente.">
</head>
<body>
  
    <nav class="navbar">
        <div class="container">
            <div class="logo">
                <i class="fas fa-recycle"></i>
                <span>ReciclApp</span>
            </div>
            <ul class="nav-links">
                <li><a href="#inicio">Inicio</a></li>
                <li><a href="#comofunciona">¿Cómo Funciona?</a></li>
                <li><a href="#servicios">Servicios</a></li>
                <li><a href="#testimonios">Testimonios</a></li>
                <li><a href="login.php" class="btn-login">Iniciar Sesión</a></li>
            </ul>
            <div class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </nav>

    <!-- Sección Hero -->
    <header class="hero" id="inicio">
        <div class="container">
            <div class="hero-content">
                <h1>Transforma residuos en recompensas</h1>
                <p>Únete a nuestra comunidad, recicla de forma inteligente y obtén beneficios por ayudar al planeta.</p>
                <div class="cta-buttons">
                    <a href="register.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Regístrate ahora
                    </a>
                    <a href="#comofunciona" class="btn btn-secondary">
                        <i class="fas fa-info-circle"></i> Aprende cómo
                    </a>
                </div>
            </div>
        </div>
    </header>

  
    <section id="comofunciona" class="how-it-works">
        <div class="container">
            <h2 class="section-title">¿Cómo Funciona?</h2>
            <div class="steps">
                <div class="step">
                    <div class="step-icon">
                        <div class="step-number">1</div>
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h3>Regístrate</h3>
                    <p>Crea tu cuenta gratis en segundos y únete a la comunidad.</p>
                </div>
                <div class="step">
                    <div class="step-icon">
                        <div class="step-number">2</div>
                        <i class="fas fa-trash-alt"></i>
                    </div>
                    <h3>Recicla</h3>
                    <p>Separa tus residuos y llévalos a un punto de recolección cercano.</p>
                </div>
                <div class="step">
                    <div class="step-icon">
                        <div class="step-number">3</div>
                        <i class="fas fa-star"></i>
                    </div>
                    <h3>Gana Puntos</h3>
                    <p>Acumula puntos por cada kilogramo de material que recicles.</p>
                </div>
                <div class="step">
                    <div class="step-icon">
                        <div class="step-number">4</div>
                        <i class="fas fa-gift"></i>
                    </div>
                    <h3>Canjea</h3>
                    <p>Usa tus puntos para obtener increíbles premios y descuentos.</p>
                </div>
            </div>
        </div>
    </section>
    
    
    <section class="carousel-section">
        <div class="container">
            <h2 class="section-title">Nuestra Comunidad en Acción</h2>
            <div class="carousel">
                <div class="slide active">
                    <img src="media/joven1.jpg" alt="Joven reciclando">
                    <div class="slide-caption">
                        <p>"Gracias a ReciclApp he aprendido a reciclar mejor."</p>
                    </div>
                </div>
                <div class="slide">
                    <img src="media/joven2.jpg" alt="Personas reciclando">
                    <div class="slide-caption">
                        <p>"Los puntos que gano los canjeo por productos ecológicos."</p>
                    </div>
                </div>
                <div class="slide">
                    <img src="media/jovenes2.avif" alt="Día del reciclaje">
                    <div class="slide-caption">
                        <p>"Una forma fácil de contribuir al medio ambiente."</p>
                    </div>
                </div>
                <div class="carousel-controls">
                    <button class="prev"><i class="fas fa-chevron-left"></i></button>
                    <button class="next"><i class="fas fa-chevron-right"></i></button>
                </div>
                <div class="carousel-indicators"></div>
            </div>
        </div>
    </section>

    <!-- Servicios -->
    <section id="servicios" class="services">
        <div class="container">
            <h2 class="section-title">Nuestros Servicios</h2>
            <div class="services-grid">
                <div class="service-box">
                    <div class="service-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h3>Puntos de Recolección</h3>
                    <p>Encuentra fácilmente los centros de acopio más cercanos a ti con nuestro mapa interactivo.</p>
                </div>
                <div class="service-box">
                    <div class="service-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3>Educación Ambiental</h3>
                    <p>Accede a guías y talleres para aprender a separar residuos y reducir tu impacto.</p>
                </div>
                <div class="service-box">
                    <div class="service-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                    <h3>Sistema de Puntos</h3>
                    <p>Gana puntos por cada material reciclado y canjéalos por productos y servicios sostenibles.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonios -->
    <section id="testimonios" class="testimonials">
        <div class="container">
            <h2 class="section-title">Lo que dicen nuestros usuarios</h2>
            <div class="testimonial-carousel">
                <div class="testimonial active">
                    <div class="testimonial-content">
                        <img src="media/Captura de pantalla 2025-04-04 231916.png" alt="Usuario Violeta">
                        <div class="testimonial-text">
                            <p>"¡ReciclApp ha cambiado mis hábitos! Ahora toda mi familia recicla. Los puntos son un gran incentivo y la app es muy fácil de usar."</p>
                            <h4>- Lic. Violeta</h4>
                            <div class="stars">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

   
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section about">
                    <h3>ReciclApp</h3>
                    <p>Transformando residuos en oportunidades para un futuro sostenible.</p>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="footer-section links">
                    <h3>Enlaces Rápidos</h3>
                    <ul>
                        <li><a href="#inicio">Inicio</a></li>
                        <li><a href="#comofunciona">¿Cómo Funciona?</a></li>
                        <li><a href="#servicios">Servicios</a></li>
                        <li><a href="#testimonios">Testimonios</a></li>
                    </ul>
                </div>
                <div class="footer-section legal">
                    <h3>Legal</h3>
                    <ul>
                        <li><a href="#">Términos y Condiciones</a></li>
                        <li><a href="#">Política de Privacidad</a></li>
                    </ul>
                </div>
                <div class="footer-section contact">
                    <h3>Contacto</h3>
                    <ul>
                        <li><i class="fas fa-phone"></i> +57 313 7986621</li>
                        <li><i class="fas fa-envelope"></i> reciclapp@gmail.com</li>
                        <li><i class="fas fa-map-marker-alt"></i> Quibdó - Chocó, Colombia</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 ReciclApp. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="js/script.js"></script>
</body>
</html>