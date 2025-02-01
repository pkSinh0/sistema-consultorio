class CameraHandler {
    constructor() {
        this.stream = null;
        this.usandoCameraFrontal = true;
        this.constraints = {
            video: {
                facingMode: 'user',
                width: { ideal: 1280 },
                height: { ideal: 720 }
            },
            audio: false
        };
    }

    async iniciarCamera() {
        try {
            this.stream = await navigator.mediaDevices.getUserMedia(this.constraints);
            return this.stream;
        } catch (err) {
            // Tentar configurações mais básicas se falhar
            try {
                this.constraints.video = {
                    facingMode: this.usandoCameraFrontal ? 'user' : 'environment'
                };
                this.stream = await navigator.mediaDevices.getUserMedia(this.constraints);
                return this.stream;
            } catch (err2) {
                throw new Error('Não foi possível acessar a câmera: ' + err2.message);
            }
        }
    }

    async alternarCamera() {
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
        }
        
        this.usandoCameraFrontal = !this.usandoCameraFrontal;
        this.constraints.video.facingMode = this.usandoCameraFrontal ? 'user' : 'environment';
        
        return await this.iniciarCamera();
    }

    pararCamera() {
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.stream = null;
        }
    }
} 