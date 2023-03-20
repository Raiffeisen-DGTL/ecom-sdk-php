(({
    publicId,
    formData,
    url = 'https://e-commerce.raiffeisen.ru/pay',
    method = 'openPopup',
    sdk = 'PaymentPageSdk',
    src = 'https://pay.raif.ru/pay/sdk/v2/payment.styled.min.js'
}) => new Promise((resolve, reject) => {
    const openPopup = () => {
        new this[sdk](publicId, {url})[method](formData).then(resolve).catch(reject);
    };
    if (!this.hasOwnProperty(sdk)) {
        const script = this.document.createElement('script');
        script.src = src;
        script.onload = openPopup;
        script.onerror = reject;
        this.document.head.appendChild(script);
    } else openPopup();
}))($request)
