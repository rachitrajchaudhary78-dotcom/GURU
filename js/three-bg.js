/**
 * Three.js 3D Animated Background
 * Renders an interactive wireframe Torus Knot and a slow-moving particle field.
 * Dynamically reacts to mouse tracking and theme switching.
 */
$(document).ready(function() {
    const canvas = document.getElementById('three-bg');
    if (!canvas) return;

    // 1. Initialize Scene & Camera
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
    camera.position.z = 25;

    // 2. WebGL Renderer
    const renderer = new THREE.WebGLRenderer({ canvas: canvas, alpha: true, antialias: true });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

    // Helper to get theme colors
    function getThemeColor() {
        const isDark = $('body').hasClass('dark-mode') || $('html').hasClass('dark-mode');
        // Glowing cyan in dark mode (#0ea5e9), soft purple in light mode (#7c3aed)
        return isDark ? new THREE.Color(0x0ea5e9) : new THREE.Color(0x7c3aed);
    }

    // 3. Create 3D Objects
    // A: Rotating Torus Knot (Wireframe)
    const torusGeometry = new THREE.TorusKnotGeometry(8, 2.2, 100, 16);
    const torusMaterial = new THREE.MeshBasicMaterial({
        color: getThemeColor(),
        wireframe: true,
        transparent: true,
        opacity: 0.14
    });
    const torusKnot = new THREE.Mesh(torusGeometry, torusMaterial);
    scene.add(torusKnot);

    // B: Floating Particle System (Depth effect)
    const particlesCount = 100;
    const particleGeometry = new THREE.BufferGeometry();
    const positions = new Float32Array(particlesCount * 3);

    for (let i = 0; i < particlesCount * 3; i++) {
        // Spread particles randomly in a large bounding box
        positions[i] = (Math.random() - 0.5) * 60;
    }

    particleGeometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));

    const particleMaterial = new THREE.PointsMaterial({
        color: getThemeColor(),
        size: 0.28,
        transparent: true,
        opacity: 0.6,
        sizeAttenuation: true
    });

    const particleSystem = new THREE.Points(particleGeometry, particleMaterial);
    scene.add(particleSystem);

    // 4. Mouse Move Event Interactivity
    let mouseX = 0;
    let mouseY = 0;
    let targetX = 0;
    let targetY = 0;

    $(window).on('mousemove', function(event) {
        // Map mouse coordinates to range [-1, 1]
        mouseX = (event.clientX - window.innerWidth / 2) / (window.innerWidth / 2);
        mouseY = (event.clientY - window.innerHeight / 2) / (window.innerHeight / 2);
    });

    // 5. Dynamic Window Resizing
    window.addEventListener('resize', () => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    });

    // 6. Animation Loop
    const clock = new THREE.Clock();

    function animate() {
        requestAnimationFrame(animate);

        const elapsedTime = clock.getElapsedTime();

        // Object rotations
        torusKnot.rotation.x = elapsedTime * 0.05;
        torusKnot.rotation.y = elapsedTime * 0.07;

        particleSystem.rotation.x = elapsedTime * 0.015;
        particleSystem.rotation.y = elapsedTime * 0.02;

        // Smooth camera movement using lerp (mouse tracking)
        targetX = mouseX * 4;
        targetY = mouseY * 4;
        camera.position.x += (targetX - camera.position.x) * 0.05;
        camera.position.y += (-targetY - camera.position.y) * 0.05;
        camera.lookAt(scene.position);

        // Update colors dynamically if the theme toggles
        const currentColor = getThemeColor();
        if (!torusMaterial.color.equals(currentColor)) {
            // Smoothly lerp towards the new color
            torusMaterial.color.lerp(currentColor, 0.1);
            particleMaterial.color.lerp(currentColor, 0.1);
        }

        renderer.render(scene, camera);
    }

    animate();
});
