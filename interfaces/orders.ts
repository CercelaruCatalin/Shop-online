export default interface Orders {
    id: number;
    name: string;
    phone: string;
    email: string;
    method: string;
    address: string;
    placed_on: string;
    payment_status: string;
    status: string;
    total_price: number;
    items: { product_id: number; quantity: number; price_per_item: number }[];
}